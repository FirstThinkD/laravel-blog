<?php

namespace WebDevEtc\BlogEtc\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use WebDevEtc\BlogEtc\Events\CategoryAdded;
use WebDevEtc\BlogEtc\Events\CategoryEdited;
use WebDevEtc\BlogEtc\Events\CategoryWillBeDeleted;
use WebDevEtc\BlogEtc\Helpers;
use WebDevEtc\BlogEtc\Middleware\UserCanManageBlogPosts;
use WebDevEtc\BlogEtc\Models\HessamCategory;
use WebDevEtc\BlogEtc\Models\HessamCategoryTranslation;
use WebDevEtc\BlogEtc\Models\HessamLanguage;
use WebDevEtc\BlogEtc\Requests\DeleteBlogEtcCategoryRequest;
use WebDevEtc\BlogEtc\Requests\StoreBlogEtcCategoryRequest;
use WebDevEtc\BlogEtc\Requests\UpdateBlogEtcCategoryRequest;

/**
 * Class HessamCategoryAdminController
 * @package WebDevEtc\BlogEtc\Controllers
 */
class HessamCategoryAdminController extends Controller
{
    /**
     * HessamCategoryAdminController constructor.
     */
    public function __construct()
    {
        $this->middleware(UserCanManageBlogPosts::class);
    }

    /**
     * Show list of categories
     *
     * @return mixed
     */
    public function index(Request $request){
        $language_id = $request->cookie('language_id');
        $categories = HessamCategory::orderBy("id")->paginate(25);
        return view("blogetc_admin::categories.index",[
            'categories' => $categories,
            'language_id' => $language_id
        ]);
    }

    /**
     * Show the form for creating new category
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create_category(Request $request){
        $language_id = $request->cookie('language_id');
        $language_list = HessamLanguage::where('active',true)->get();

        return view("blogetc_admin::categories.add_category",[
            'category' => new \WebDevEtc\BlogEtc\Models\HessamCategory(),
            'category_translation' => new \WebDevEtc\BlogEtc\Models\HessamCategoryTranslation(),
            'categories_list' => HessamCategory::orderBy("id")->get(),
            'language_id' => $language_id,
            'language_list' => $language_list
        ]);
    }

    /**
     * Store a new category
     *
     * @param StoreBlogEtcCategoryRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * This controller is totally REST controller
     */
    public function store_category(Request $request){
        $language_id = $request->cookie('language_id');
        $language_list = $request['data'];

        if ($request['parent_id']== 0){
            $request['parent_id'] = null;
        }
        $new_category = HessamCategory::create([
            'parent_id' => $request['parent_id']
        ]);

        foreach ($language_list as $key => $value) {
            //check for slug availability
            $obj = HessamCategoryTranslation::where('slug',$value['slug'])->first();
            if ($obj){
                HessamCategory::destroy($new_category->id);
                return response()->json([
                    'code' => 403,
                    'message' => "slug is already taken",
                    'data' => $value['lang_id']
                ]);
            }
            $new_category_translation = $new_category->categoryTranslations()->create([
                'category_name' => $value['category_name'],
                'slug' => $value['slug'],
                'category_description' => $value['category_description'],
                'lang_id' => $value['lang_id'],
                'category_id' => $new_category->id
            ]);
        }

        event(new CategoryAdded($new_category, $new_category_translation));
        Helpers::flash_message("Saved new category");
        return redirect( route('blogetc.admin.categories.index') );
    }

    /**
     * Show the edit form for category
     * @param $categoryId
     * @return mixed
     */
    public function edit_category($categoryId){
        $category = HessamCategory::findOrFail($categoryId);
        return view("blogetc_admin::categories.edit_category",[
            'categories_list' => HessamCategory::orderBy("category_name")->get()
        ])->withCategory($category);
    }

    /**
     * Save submitted changes
     *
     * @param UpdateBlogEtcCategoryRequest $request
     * @param $categoryId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update_category(UpdateBlogEtcCategoryRequest $request, $categoryId){
        /** @var HessamCategory $category */
        $category = HessamCategory::findOrFail($categoryId);
        $category->fill($request->all());
        $category->save();

        Helpers::flash_message("Saved category changes");
        event(new CategoryEdited($category));
        return redirect($category->edit_url());
    }

    /**
     * Delete the category
     *
     * @param DeleteBlogEtcCategoryRequest $request
     * @param $categoryId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function destroy_category(DeleteBlogEtcCategoryRequest $request, $categoryId){

        /* Please keep this in, so code inspections don't say $request was unused. Of course it might now get marked as left/right parts are equal */
        $request=$request;

        $category = HessamCategory::findOrFail($categoryId);
        event(new CategoryWillBeDeleted($category));
        $category->delete();

        return view ("blogetc_admin::categories.deleted_category");

    }

}

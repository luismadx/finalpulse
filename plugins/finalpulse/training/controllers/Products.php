<?php namespace Finalpulse\Training\Controllers;

use Finalpulse\Training\Models\Product;
use Auth, Input, Validator;
use Winter\Storm\Exception\ValidationException;

class Products
{
    /* lista por categoria */
    public static function listByCategory(int $cat)
    {
        $u = Auth::getUser();
        $q = Product::where('id_categories',$cat)
            ->leftJoin('f_categories as c','f_products.id_categories','=','c.id')
            ->select('f_products.*','c.user_id');

        if(!$u->groups()->where('code','admin')->exists())
            $q->where('c.user_id',$u->id);

        return $q->get();
    }

    /* grava */
    public static function save(array $post)
    {
        $rules = (new Product)->rules + ['image'=>'nullable|file|image'];
        $v = Validator::make($post,$rules);
        if($v->fails()) throw new ValidationException($v);

        $prod = Product::persist($post+['image'=>Input::file('image')]);

        return [
            'success'=>true,
            'id'=>$prod->id,
            'user_id'=>Auth::id(),
            'slug'=>$prod->slug,
            'image'=>$prod->image,
        ]+$prod->only([
            'name','description','minutes',
            'intro_title','intro_subtitle','intro_action','intro_action_description',
            'intro_learning_objectives','intro_estimated_duration','intro_course_structure',
            'intro_warning','intro_warning_description','intro_button_start',
            'final_title','final_subtitle','final_skills','final_support',
        ]);
    }

    public static function find(int $id){ return Product::findOrFail($id); }
}

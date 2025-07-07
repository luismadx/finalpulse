<?php namespace FinalPulse\Training\Components;

use Cms\Classes\ComponentBase;
use Auth;
use Input;
use FinalPulse\Training\Models\Category;

class CategoryGrid extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'CategoryGrid',
            'description' => 'Lista, cria e edita categorias no frontend',
        ];
    }

    public function onRun()
    {
        $this->page['categories'] = $this->loadCategories();
    }

    /* ---------- handlers AJAX ---------- */
    public function onCreateCategory()
    {
        $cat        = new Category(post());
        $cat->image = $cat->saveUploadedImage(Input::file('image'));
        $cat->save();

        $this->page['categories'] = $this->loadCategories();
        return ['#cardsContainer' => $this->renderPartial('@cards')];
    }

    public function onUpdateCategory()
    {
        $cat = Category::forUser(Auth::getUser())->findOrFail(post('id'));
        $cat->fill(post());
        $cat->image = $cat->saveUploadedImage(Input::file('image'), $cat->image);
        $cat->save();

        $this->page['categories'] = $this->loadCategories();
        return ['#cardsContainer' => $this->renderPartial('@cards')];
    }

    /* ---------- helpers ---------- */
    protected function loadCategories()
    {
        return Category::forUser(Auth::getUser())->get();
    }
}

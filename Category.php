<?php namespace Finalpulse\Training\Models;

use Model;
use Auth;
use Winter\User\Models\User;

class Category extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table      = 'f_categories';
    public $timestamps = false;           // a tabela não tem created_at / updated_at

    protected $fillable = ['user_id', 'name', 'description', 'image'];

    /**
     * Regras de validação      * – name é obrigatório, 3-250 car.
     * – description até 250 car.
     * – name deve ser único por utilizador 
     */
    public $rules = [
        'name'        => 'required|between:3,250',
        'description' => 'nullable|max:250',
    ];

    public $customMessages = [
        'name.required' => 'O campo Nome é obrigatório.',
        'name.between'  => 'O Nome deve ter entre 3 e 250 caracteres.',
        'description.max' => 'A Descrição não pode ultrapassar 250 caracteres.',
    ];

    public $belongsTo = [
        'user' => [User::class],
    ];

    /* ---------- gancho p/ unicidade por utilizador ---------- */
    public function beforeValidate()
    {
        $this->rules['name'] .= '|unique:f_categories,name,' .
            ($this->id ?: 'NULL') . ',id,user_id,' . Auth::id();
    }

    public function beforeCreate()
    {
        $this->user_id = Auth::id();
    }

    /* scope para filtrar por permissões */
    public function scopeForUser($q, $user)
    {
        if (!$user) return $q->whereRaw('1=0');

        return $user->groups()->where('code', 'admin')->exists()
            ? $q
            : $q->where('user_id', $user->id);
    }
}

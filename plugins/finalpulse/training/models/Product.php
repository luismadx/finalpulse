<?php namespace Finalpulse\Training\Models;

use Model;
use Winter\Storm\Support\Str;

class Product extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'f_products';
    protected $guarded = [];

    /* validação */
    public $rules = [
        'name'                       => 'required|max:255',
        'description'                => 'nullable|max:255',
        'minutes'                    => 'nullable|integer',
        'intro_title'                => 'nullable|max:255',
        'intro_subtitle'             => 'nullable|max:255',
        'intro_action'               => 'nullable|max:255',
        'intro_action_description'   => 'nullable|max:255',
        'intro_learning_objectives'  => 'nullable',
        'intro_estimated_duration'   => 'nullable|max:255',
        'intro_course_structure'     => 'nullable',
        'intro_warning'              => 'nullable|max:255',
        'intro_warning_description'  => 'nullable|max:255',
        'intro_button_start'         => 'nullable|max:100',
        'final_title'                => 'nullable|max:200',
        'final_subtitle'             => 'nullable|max:255',
        'final_skills'               => 'nullable',
        'final_support'              => 'nullable|max:255',
    ];

    /* slug único */
    public static function makeSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base; $i = 1;
        while (self::where('slug',$slug)->when($ignore,fn($q)=>$q->where('id','!=',$ignore))->exists())
            $slug = $base.'-'.($i++);
        return $slug;
    }

    /* redimensiona miniatura */
    public static function resize($src,$dst,$w=390,$h=390): bool
    {
        [$ow,$oh,$t]=getimagesize($src);
        $srcImg = match($t){
            IMAGETYPE_JPEG=>imagecreatefromjpeg($src),
            IMAGETYPE_PNG =>imagecreatefrompng($src),
            default=>false
        };
        if(!$srcImg) return false;
        $dstImg=imagecreatetruecolor($w,$h);
        if($t===IMAGETYPE_PNG){
            imagealphablending($dstImg,false); imagesavealpha($dstImg,true);
            $tr=imagecolorallocatealpha($dstImg,255,255,255,127);
            imagefilledrectangle($dstImg,0,0,$w,$h,$tr);
        }
        $sr=$ow/$oh; $tg=$w/$h;
        if($sr>$tg){$cw=intval($oh*$tg);$ox=intval(($ow-$cw)/2);$oy=0;$ch=$oh;}
        else        {$ch=intval($ow/$tg);$oy=intval(($oh-$ch)/2);$ox=0;$cw=$ow;}
        imagecopyresampled($dstImg,$srcImg,0,0,$ox,$oy,$w,$h,$cw,$ch);
        $t===IMAGETYPE_JPEG?imagejpeg($dstImg,$dst,90):imagepng($dstImg,$dst,9);
        imagedestroy($srcImg); imagedestroy($dstImg);
        return true;
    }

    /* cria/actualiza */
    public static function persist(array $data): self
    {
        $id   = $data['id'] ?? null;
        $mdl  = $id ? self::findOrFail($id) : new self();
        $mdl->slug = self::makeSlug($data['name'],$id);
        $mdl->fill($data);
        $mdl->minutes = (int)($data['minutes'] ?? 0);

        /* imagem */
        if(isset($data['image']) && $data['image']->isValid()){
            $fld = storage_path('app/public/uploads/products');
            if(!is_dir($fld)) mkdir($fld,0755,true);
            $fn = time().'_'.preg_replace('/[^\w\.\-]/','_',$data['image']->getClientOriginalName());
            $data['image']->move($fld,$fn);
            self::resize("$fld/$fn","$fld/$fn");
            if($id && $mdl->image) @unlink("$fld/{$mdl->image}");
            $mdl->image = $fn;
        }

        $mdl->save();
        return $mdl;
    }
}

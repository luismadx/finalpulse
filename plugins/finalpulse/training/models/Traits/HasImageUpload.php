<?php namespace FinalPulse\Training\Models\Traits;

use Illuminate\Http\UploadedFile;

trait HasImageUpload
{
    protected function saveUploadedImage(?UploadedFile $file, ?string $old = null): ?string
    {
        if (!$file) return $old;

        $folder = storage_path('app/public/uploads/categories');
        @mkdir($folder, 0755, true);

        $name = time().'_'.preg_replace('/[^A-Za-z0-9\-\_\.]/', '_',
                 $file->getClientOriginalName());
        $path = $folder.'/'.$name;
        $file->move($folder, $name);

        $this->resize($path, 390, 390);

        if ($old) @unlink($folder.'/'.$old);
        return $name;
    }

    /* redimensiona mantendo proporção e preenchendo 390×390 */
    protected function resize($p, $tw, $th)
    {
        [$w,$h,$t] = getimagesize($p);
        $src = match($t){
            IMAGETYPE_JPEG => imagecreatefromjpeg($p),
            IMAGETYPE_PNG  => imagecreatefrompng($p),
            default        => null,
        };
        if(!$src) return;

        $dst = imagecreatetruecolor($tw,$th);
        if($t==IMAGETYPE_PNG){
            imagealphablending($dst,false);
            imagesavealpha($dst,true);
        }

        $sr=$w/$h; $tr=$tw/$th;
        $cw=$sr>$tr?intval($h*$tr):$w;
        $ch=$sr>$tr?$h:intval($w/$tr);
        $sx=$sr>$tr?intval(($w-$cw)/2):0;
        $sy=$sr>$tr?0:intval(($h-$ch)/2);

        imagecopyresampled($dst,$src,0,0,$sx,$sy,$tw,$th,$cw,$ch);
        $t==IMAGETYPE_JPEG?imagejpeg($dst,$p,90):imagepng($dst,$p,9);
        imagedestroy($src); imagedestroy($dst);
    }
}

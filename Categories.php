<?php namespace Finalpulse\Training\Controllers;

use Finalpulse\Training\Models\Category;
use Auth;
use Input;
use Response;
use Illuminate\Http\UploadedFile;

/** Controller para as categorias */
class Categories
{
    /**
     * Devolve as categorias visíveis ao utilizador actual.
     * Exemplo de uso em teste.htm:
     *     $this['categories'] = Categories::listForCurrentUser();
     */
    public static function listForCurrentUser()
    {
        $user = Auth::getUser();

        $query = Category::query();

        // Admins vêem tudo; managers só as suas
        if (!$user->groups()->where('code', 'admin')->exists()) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * Handler AJAX →  <form data-request="onCreateCategory">
     */
    public static function onCreateCategory()
    {
        $user = Auth::getUser();

        $cat           = new Category(Input::only(['name', 'description']));
        $cat->user_id  = $user->id;
        $cat->image    = self::processImage();        // upload + resize

        if (!$cat->save()) {
            // Regras de validação falharam
            return [
                'success' => false,
                'errors'  => $cat->errors()->all(),
            ];
        }

        return self::jsonSuccess($cat);
    }

    /**
     * Handler AJAX →  <form data-request="onUpdateCategory">
     */
    public static function onUpdateCategory()
    {
        $user = Auth::getUser();
        $id   = post('id');

        $cat = Category::where('id', $id)
            ->where('user_id', $user->id)      // impede editar alheio
            ->first();

        if (!$cat) {
            return Response::make(
                ['success' => false, 'message' => 'Categoria não encontrada'],
                404
            );
        }

        $cat->fill(Input::only(['name', 'description']));
        $cat->image = self::processImage($cat);       // novo upload se houver

        if (!$cat->save()) {
            return [
                'success' => false,
                'errors'  => $cat->errors()->all(),
            ];
        }

        return self::jsonSuccess($cat);
    }

    /* --------------------------------------------------------------------
       HELPERS INTERNOS (upload de imagem + JSON normalizado)
       -------------------------------------------------------------------- */

    /** Faz upload, gera thumb 390×390 e devolve nome do ficheiro. Se existir for passado, remove a imagem antiga.
     */
    private static function processImage(?Category $existing = null): ?string
    {
        /** @var UploadedFile|null $file */
        $file = Input::file('image');
        if (!$file) {
            return $existing?->image;   // sem upload → mantém
        }

        $folder = storage_path('app/public/uploads/categories');
        @mkdir($folder, 0755, true);

        $name = time() . '_' . preg_replace(
            '/[^A-Za-z0-9\-\_\.]/',
            '_',
            $file->getClientOriginalName()
        );

        $path = $folder . '/' . $name;
        $file->move($folder, $name);

        self::resizeImage($path, 390, 390);

        // apaga ficheiro antigo
        if ($existing && $existing->image && is_file($folder.'/'.$existing->image)) {
            @unlink($folder.'/'.$existing->image);
        }

        return $name;
    }

    /**
     * Redimensiona mantendo proporção e preenchendo todo o canvas.
     */
    private static function resizeImage(string $p, int $tw, int $th): void
    {
        [$w,$h,$t] = getimagesize($p);
        $src = match ($t) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($p),
            IMAGETYPE_PNG  => imagecreatefrompng($p),
            default        => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($tw, $th);
        if ($t === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        $srcRatio = $w / $h;
        $tgtRatio = $tw / $th;

        if ($srcRatio > $tgtRatio) {
            // recorta laterais
            $cropH = $h;
            $cropW = intval($h * $tgtRatio);
            $sx    = intval(($w - $cropW) / 2);
            $sy    = 0;
        } else {
            // recorta topo/baixo
            $cropW = $w;
            $cropH = intval($w / $tgtRatio);
            $sx    = 0;
            $sy    = intval(($h - $cropH) / 2);
        }

        imagecopyresampled($dst, $src,
            0, 0, $sx, $sy,
            $tw, $th,
            $cropW, $cropH
        );

        $t === IMAGETYPE_JPEG ? imagejpeg($dst, $p, 90) : imagepng($dst, $p, 9);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Formato de resposta de sucesso.
     */
    private static function jsonSuccess(Category $c): array
    {
        return [
            'success'     => true,
            'id'          => $c->id,
            'name'        => $c->name,
            'description' => $c->description,
            'image'       => $c->image,
        ];
    }
}

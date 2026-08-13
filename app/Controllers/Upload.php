<?php

namespace App\Controllers;

use App\Models\UploadModel;

class Upload extends BaseController
{
    protected $helpers = ['form'];

    public function index()
    {
        return view('upload/upload_form', ['errors' => []]);
    }

    public function uploadLogo()
    {
        $uploadModel = new UploadModel();
        $userData = $uploadModel->first();

        return view('upload/upload_logo', ['errors' => [], 'userData' => $userData]);
    }

    public function upload()
    {
        $modelUpload = new UploadModel();

        $mainColor = $this->request->getVar('mainColor');
        $secondaryColor = $this->request->getVar('secondaryColor');

        $validationRule = [
            'userfile' => [
                'label' => 'The selected file',
                'rules' => 'uploaded[userfile]'
                    . '|is_image[userfile]'
                    . '|mime_in[userfile,image/jpg,image/jpeg,image/gif,image/png,image/webp]'
                    . '|max_size[userfile,2000]'
                    . '|max_dims[userfile,3600,5000]',
            ],
        ];

        if (!$this->validate($validationRule)) {
            $query = [
                'main_color' => $mainColor,
                'secondary_color' => $secondaryColor,
            ];

            $existingLogo = $modelUpload->first();

            if ($existingLogo) {
                $modelUpload->update($existingLogo['id'], $query);
            }

            $data = ['errors' => 'Datos guardados con Ã©xito.'];

            return redirect()->to('uploadLogo')->with('msg', ['type' => 'success', 'body' => $data]);
        }

        $img = $this->request->getFile('userfile');

        if ($img->hasMoved()) {
            $data = ['errors' => 'The file has already been moved.'];

            return view('upload/upload_logo', $data);
        }

        $targetDirectory = ROOTPATH . 'public/assets/images/uploads';
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            return redirect()->to('uploadLogo')->with('msg', ['type' => 'danger', 'body' => ['No pudimos crear la carpeta de logos en este servidor.']]);
        }

        $fileExtension = strtolower((string) ($img->guessExtension() ?: $img->getClientExtension() ?: pathinfo($img->getName(), PATHINFO_EXTENSION) ?: 'png'));
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'gif', 'png', 'webp'], true)) {
            $fileExtension = 'png';
        }

        $fileName = uniqid('', true) . '.' . $fileExtension;

        $query = [
            'name' => $fileName,
            'main_color' => $mainColor,
            'secondary_color' => $secondaryColor,
        ];

        try {
            $moved = $img->move($targetDirectory, $fileName, true);
        } catch (\Throwable $exception) {
            log_message('error', 'No se pudo mover el logo subido: ' . $exception->getMessage());
            return redirect()->to('uploadLogo')->with('msg', ['type' => 'danger', 'body' => ['No pudimos guardar el archivo subido.']]);
        }

        if (!$moved) {
            log_message('error', 'No se pudo mover el logo subido: ' . implode(' | ', $img->getErrors()));
            return redirect()->to('uploadLogo')->with('msg', ['type' => 'danger', 'body' => ['No pudimos guardar el archivo subido.']]);
        }

        $existingLogo = $modelUpload->first();
        if ($existingLogo) {
            $oldLogoFile = trim((string) ($existingLogo['name'] ?? ''));
            $modelUpload->update($existingLogo['id'], $query);

            if ($oldLogoFile !== '' && $oldLogoFile !== $fileName) {
                $oldLogoPath = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldLogoFile;
                if (is_file($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }
        } else {
            $modelUpload->insert($query);
        }

        $data = ['errors' => 'Datos guardados con Ã©xito.'];

        return redirect()->to('uploadLogo')->with('msg', ['type' => 'success', 'body' => $data]);
    }

    public function deleteBackground()
    {
        $modelUpload = new UploadModel();

        $bg = $modelUpload->first();

        if ($bg) {
            $modelUpload->delete($bg['id']);
            return redirect()->to(base_url('abmAdmin'))->with('msg', ['type' => 'success', 'body' => 'Eliminado con Ã©xito']);
        } else {
            return redirect()->to(base_url('abmAdmin'))->with('msg', ['type' => 'danger', 'body' => 'No hay archivos para eliminar']);
        }
    }
}

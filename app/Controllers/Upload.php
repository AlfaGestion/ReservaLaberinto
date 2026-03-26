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
                    . '|max_dims[userfile,3600,5000]'
            ],
        ];

        if (!$this->validate($validationRule)) {
            $query = [
                'main_color' => $mainColor,
                'secondary_color' => $secondaryColor
            ];

            $existingLogo = $modelUpload->first();

            if ($existingLogo) {
                $modelUpload->update($existingLogo['id'], $query);
            }

            $data = ['errors' => 'Datos guardados exitosamente.'];

            return redirect()->to('uploadLogo')->with('msg', ['type' => 'success', 'body' => $data]);
        } else {

            $img = $this->request->getFile('userfile');


            if (!$img->hasMoved()) {
                $extension = explode('.', $img->getName());
                $fileName = uniqid() . '.' . $extension[count($extension) - 1];

                $query = [
                    'name' => $fileName,
                    'main_color' => $mainColor,
                    'secondary_color' => $secondaryColor
                ];

                $existingLogo = $modelUpload->first();

                if ($existingLogo) {
                    $modelUpload->delete($existingLogo['id']);
                }

                $modelUpload->insert($query);

                $img->move(ROOTPATH . 'public/assets/images/uploads', $fileName);

                $data = ['errors' => 'Datos guardados exitosamente.'];

                return redirect()->to('uploadLogo')->with('msg', ['type' => 'success', 'body' => $data]);
            }

            $data = ['errors' => 'The file has already been moved.'];

            return view('upload/upload_logo', $data);
        }
    }

    public function deleteBackground()
    {
        $modelUpload = new UploadModel();

        $bg = $modelUpload->first();

        if ($bg) {
            $modelUpload->delete($bg['id']);
            return redirect()->to(base_url('abmAdmin'))->with('msg', ['type' => 'success', 'body' => 'Eliminado correctamente']);
        } else {
            return redirect()->to(base_url('abmAdmin'))->with('msg', ['type' => 'danger', 'body' => 'No hay archivos para eliminar']);
        }
    }
}

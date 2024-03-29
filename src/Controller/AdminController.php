<?php

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Page;
use App\Entity\Image;

/**
 * @Route("/admin", options={ "i18n": false })
 */
class AdminController extends BaseAdminController
{   
    /**
     * @Route("/dashboard", name="admin.dashboard")
     */
    public function dashboard()
    {
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * @Route("/asset/upload", name="admin.asset_upload", options={ "expose": true })
     */
    public function assetUpload(Request $request)
    {   
        $em = $this->getDoctrine()->getManager();
        $validator = $this->get('validator');

        $assets = [];
        $errors = [];
        $files = $request->files->get('files');

        for($i=0; $i < count($files); $i++){

            $file = $files[$i];

            $uploadedFile = new Image();
            $uploadedFile->setImageFile($file);

            $violations = $validator->validate($uploadedFile)->getIterator();
            if( count($violations) > 0 ){

                foreach($violations as $violation){
                    if(!isset( $errors[$i] )){
                        $errors[$i] = [ 
                            "filename" => $violation->getRoot()->getImageFile()->getClientOriginalName(),
                            "violations" => []
                        ];
                    }
                    $errors[$i]["violations"][] = $violation->getMessage();
                }

            }else{

                $assets[] = $uploadedFile;
                $em->persist($uploadedFile);

            }

        }
        $em->flush();

        $response = [ "assets" => [], "errors" => $errors ];
        foreach($assets as $asset){
            $response["assets"][] = $this->get('vich_uploader.templating.helper.uploader_helper')->asset($asset, 'imageFile');
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/pages/link", name="admin.pages_link", options={ "expose": true })
     */
    public function getPagesLink(Request $request)
    {   
        $em = $this->getDoctrine()->getManager();
        $pages = $em->getRepository(Page::class)->findAll();

        $response = [];
        $response[] = [ "value" => "none", "name" => "None" ];

        foreach($pages as $page){
            $response[] = [ 
                "value" => "{{path('page.by_id',{ id : " . $page->getId() . ", slug : '" . $page->getSlug() . "' })}}",
                "name" => $page->getTitle()
            ];
        }

        return new JsonResponse($response);
    }
}
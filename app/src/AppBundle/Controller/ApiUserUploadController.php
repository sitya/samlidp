<?php
namespace AppBundle\Controller;

use Oneup\UploaderBundle\Controller\AbstractController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiUserUploadController extends AbstractController
{
    public function upload()
    {
        // get some basic stuff together
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getMasterRequest();
        $response = new EmptyResponse();

        // get file from request (your own logic)
        $files = $this->getFiles($request->files);

        foreach ($files as $file) {
            try {
                $this->handleUpload($file, $response, $request);
            } catch(UploadException $e) {
                // return nothing
                return new JsonResponse(array("error" => $e->getMessage()), 500);
            }
        }

        // return assembled response
        return new JsonResponse($response->assemble());

    }
}
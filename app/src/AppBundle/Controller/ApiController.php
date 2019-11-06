<?php

namespace AppBundle\Controller;

use Oneup\UploaderBundle\Uploader\ErrorHandler\NoopErrorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class ApiController
 * @package AppBundle\Controller
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     * @Route("/uploadusers", methods={"POST"})
     */
    public function uploadAction()
    {
        $config = [
            "max_size" => "1000000",
            "allowed_mimetypes" => [],
            "disallowed_mimetypes" => [],
            "namer" => "oneup_uploader.namer.uniqid",
            "use_orphanage" => false,
            "idpId" => $this->getUser()->getIdP()->getId(),
        ];
        $type = "massimport";
        $error_handler = new NoopErrorHandler();
        $oneupController = new ApiUserUploadController(
            $this->container,
            $this->get('oneup_uploader.storage.massimport_from_api'),
            $error_handler,
            $config,
            $type
        );
        $response = $oneupController->upload();

        return $response;
    }
}

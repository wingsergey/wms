<?php

namespace App\Controller\Api;

use App\Model\ApiEntityInterface;
use App\Service\CommonProcessor;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ApiController
 * @package App\Controller\Api
 */
abstract class ApiController extends Controller
{
    /**
     * @var CommonProcessor
     */
    public $commonProcessor;

    /**
     * ApiController constructor.
     * @param CommonProcessor $commonProcessor
     */
    public function __construct(CommonProcessor $commonProcessor)
    {
        $this->commonProcessor = $commonProcessor;
    }

    /**
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    public function responseAction(array $data = [], int $status = 200, array $headers = []) : JsonResponse
    {
        $jsonData = $this->detectObjectsList($data);

        if (!isset($jsonData['success'])) {
            $jsonData['success'] = true;
            if ($status >= 400) {
                $jsonData['success'] = false;
            }
        }

        return new JsonResponse($jsonData, $status, $headers);
    }

    /**
     * @param ConstraintViolationListInterface $violationList
     * @return JsonResponse
     */
    public function violationFailedResponseAction(ConstraintViolationListInterface $violationList) : JsonResponse
    {
        $errorMessages = [];
        /** @var ConstraintViolationInterface $item */
        foreach ($violationList as $item) {
            $errorMessages[$item->getPropertyPath()] = $item->getMessage();
        }

        return $this->responseAction([
            'errors'  => $errorMessages,
            'success' => false,
        ]);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    public function errorResponseAction(string $message) : JsonResponse
    {
        return $this->messageResponseAction($message, false, 200);
    }

    /**
     * @param string $message
     * @param bool $success
     * @param int $status
     * @return JsonResponse
     */
    public function messageResponseAction(string $message, bool $success = true, int $status = 200) : JsonResponse
    {
        return $this->responseAction(['message' => $message, 'success' => $success], $status);
    }

    /**
     * Converts an Exception to a Response.
     *
     * @param Request $request
     * @param \Exception|\Throwable $exception
     *
     * @throws \InvalidArgumentException
     *
     * @return JsonResponse
     */
    public function showExceptionAction(Request $request, $exception) : JsonResponse
    {
        return $this->responseAction([
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
//            'trace' => $exception->getTraceAsString(),
            'success' => false,
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    private function detectObjectsList(array $data)
    {
        if (!isset($data['objects'])) {
            return $data;
        }

        $jsonData            = $data;
        $jsonData['objects'] = [];

        if ($data['objects'] instanceof Pagerfanta) {
            foreach ($data['objects'] as $object) {
                if ($object instanceof ApiEntityInterface) {
                    $jsonData['objects'][] = $object->getApiFields();
                }
            }
            $jsonData['count'] = $data['objects']->count();
        } elseif ($data['objects'] instanceof \Doctrine\Common\Collections\ArrayCollection) {
            foreach ($data['objects'] as $object) {
                if ($object instanceof ApiEntityInterface) {
                    $jsonData['objects'][] = $object->getApiFields();
                }
            }
            $jsonData['count'] = count($data['objects']);
        } elseif ($data['objects'] instanceof ApiEntityInterface) {
            $jsonData['objects'] = [$data['objects']->getApiFields()];
            $jsonData['count']   = 1;
        } elseif (is_object($data['objects']) && method_exists($data['objects'], 'getApiFields')) {
            $jsonData['objects'] = $data['objects']->getApiFields();
            $jsonData['count']   = 1;
        } elseif ($data['objects'] instanceof \Countable || is_array($data['objects'])) {
            $jsonData['objects'] = $data['objects'];
            foreach ($jsonData['objects'] as &$object) {
                if ($object instanceof ApiEntityInterface) {
                    $object = $object->getApiFields();
                }
            }
            $jsonData['count'] = count($data['objects']);
        } else {
            $jsonData['objects'] = $data['objects'];
        }

        return $jsonData;
    }
}

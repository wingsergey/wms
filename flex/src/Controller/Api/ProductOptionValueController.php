<?php

namespace App\Controller\Api;

use App\Entity\ProductOption;
use App\Entity\ProductOptionValue;
use App\Repository\AbstractRepository;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductOptionValueController
 * @package App\Controller\Api
 *
 * @Route("/api/product-option-value")
 */
class ProductOptionValueController extends ApiController
{
    /**
     * List user product option values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *  },
     *  filters={
     *      {"name"="limit", "dataType"="integer", "description"="Limit results per page for paginated queries, default=100"}
     *  }
     * )
     *
     * @Route("", defaults={"page": "0"}, name="product_option_value_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="product_option_value_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $productOptions = $this->getDoctrine()->getRepository(ProductOptionValue::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $productOptions = $this->getDoctrine()->getRepository(ProductOptionValue::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $productOptions,
        ]);
    }

    /**
     * Get product option value by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option value is found"
     *  }
     * )
     *
     * @Route("/{id}", name="product_option_value_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id ProductOptionValue ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductOptionValue::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get product option value by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option value is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="product_option_value_get_external")
     * @Method("GET")
     *
     * @param string $id ProductOptionValue external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductOptionValue::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create product option value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productOptionId", "dataType"="UUID", "required"=true, "description"="option ID"},
     *      {"name"="type", "dataType"="UUID", "required"=true, "description"="option type", "pattern"="timestamp|string|boolean"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product option value external ID"},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="Option value title"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="product_option_value_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOptionValue::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new ProductOptionValue();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());
        /** @var ProductOption $productOption */
        $productOption = $em->getRepository(ProductOption::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('optionId'));

        $entity->setOption($productOption);

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em->persist($entity);
        $em->flush();

        return $this->responseAction([
            'objects' => $entity,
        ], 201);
    }

    /**
     * Update Product option value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productOptionId", "dataType"="UUID", "required"=false, "description"="option ID"},
     *      {"name"="type", "dataType"="UUID", "required"=false, "description"="option type", "pattern"="timestamp|string|boolean"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product option value external ID"},
     *      {"name"="value", "dataType"="string", "required"=false, "description"="Option value title"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="product_option_value_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id ProductOptionValue ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOptionValue::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(ProductOptionValue::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product option value is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('optionId')) {
            $productOption = $em->getRepository(ProductOption::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('optionId'));

            $entity->setOption($productOption);
        }

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em->persist($entity);
        $em->flush();

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Delete Product option value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productOptionValueId", "dataType"="UUID", "required"=true, "description"="Product option value ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option value is found"
     *  }
     * )
     *
     * @Route("/delete", name="product_option_value_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOptionValue::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $productOptionValue = $em->getRepository(ProductOptionValue::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productOptionValueId'));

        if (!$productOptionValue) {
            return $this->errorResponseAction('Product option value is not found or already deleted');
        }

        $em->remove($productOptionValue);
        $em->flush();

        return $this->responseAction();
    }
}

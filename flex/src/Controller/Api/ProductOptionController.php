<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductOption;
use App\Repository\AbstractRepository;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductOptionController
 * @package App\Controller\Api
 *
 * @Route("/api/product-option")
 */
class ProductOptionController extends ApiController
{
    /**
     * List user product options
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
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
     * @Route("", defaults={"page": "0"}, name="product_option_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="product_option_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $productOptions = $this->getDoctrine()->getRepository(ProductOption::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $productOptions = $this->getDoctrine()->getRepository(ProductOption::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $productOptions,
        ]);
    }

    /**
     * Get product option by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option is found"
     *  }
     * )
     *
     * @Route("/{id}", name="product_option_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id ProductOption ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductOption::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get product option by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="product_option_get_external")
     * @Method("GET")
     *
     * @param string $id ProductOption external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductOption::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create product option
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product option external ID"},
     *      {"name"="name", "dataType"="string", "required"=true, "description"="Product option title"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="product_option_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOption::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new ProductOption();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());
        /** @var Product $product */
        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));

        $entity->setProduct($product);

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
     * Update Product option
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=false, "description"="Product ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product option external ID"},
     *      {"name"="name", "dataType"="string", "required"=false, "description"="Product option title"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="product_option_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id ProductOption ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOption::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(ProductOption::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product option is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('productId')) {
            $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
            $entity->setProduct($product);
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
     * Delete Product option
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product option",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productOptionId", "dataType"="UUID", "required"=true, "description"="Product option ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product option is found"
     *  }
     * )
     *
     * @Route("/delete", name="product_option_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductOption::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $productOption = $em->getRepository(ProductOption::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productOptionId'));

        if (!$productOption) {
            return $this->errorResponseAction('Product option is not found or already deleted');
        }

        $em->remove($productOption);
        $em->flush();

        return $this->responseAction();
    }
}

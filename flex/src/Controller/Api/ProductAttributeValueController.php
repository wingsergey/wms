<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValue;
use App\Repository\AbstractRepository;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductAttributeValueController
 * @package App\Controller\Api
 *
 * @Route("/api/product-attribute-value")
 */
class ProductAttributeValueController extends ApiController
{
    /**
     * List user product attribute values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
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
     * @Route("", defaults={"page": "0"}, name="product_attribute_value_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="product_attribute_value_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $productAttributeValues = $this->getDoctrine()->getRepository(ProductAttributeValue::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $productAttributeValues = $this->getDoctrine()->getRepository(ProductAttributeValue::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $productAttributeValues,
        ]);
    }

    /**
     * Get product attribute value by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product attribute value is found"
     *  }
     * )
     *
     * @Route("/{id}", name="product_attribute_value_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id ProductAttributeValue ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductAttributeValue::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get product attribute value by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product attribute value is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="product_attribute_value_get_external")
     * @Method("GET")
     *
     * @param string $id ProductAttributeValue external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductAttributeValue::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create product attribute value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"},
     *      {"name"="attributeId", "dataType"="UUID", "required"=true, "description"="Attribute ID"},
     *      {"name"="type", "dataType"="string", "required"=true, "description"="Type", "pattern"="timestamp|string|boolean"},
     *      {"name"="value", "dataType"="string", "required"=true, "description"="Value"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="product_attribute_value_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductAttributeValue::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new ProductAttributeValue();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
        /** @var ProductAttribute $attribute */
        $attribute = $em->getRepository(ProductAttribute::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('attributeId'));

        $entity->setAttribute($attribute);
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
     * Update Product attribute value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=false, "description"="Product ID"},
     *      {"name"="attributeId", "dataType"="UUID", "required"=false, "description"="Attribute ID"},
     *      {"name"="type", "dataType"="string", "required"=false, "description"="Type", "pattern"="timestamp|string|boolean"},
     *      {"name"="value", "dataType"="string", "required"=false, "description"="Value"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="product_attribute_value_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id ProductAttributeValue ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductAttributeValue::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(ProductAttributeValue::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product attribute value is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('productId')) {
            $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
            $entity->setProduct($product);
        }

        if ($request->request->has('attributeId')) {
            $attribute = $em->getRepository(ProductAttribute::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('attributeId'));
            $entity->setAttribute($attribute);
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
     * Delete Product attribute value
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product attribute value",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productAttributeValueId", "dataType"="UUID", "required"=true, "description"="Product attribute value ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product attribute value is found"
     *  }
     * )
     *
     * @Route("/delete", name="product_attribute_value_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductAttributeValue::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $productAttributeValue = $em->getRepository(ProductAttributeValue::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productAttributeValueId'));

        if (!$productAttributeValue) {
            return $this->errorResponseAction('Product attribute value is not found or already deleted');
        }

        $em->remove($productAttributeValue);
        $em->flush();

        return $this->responseAction();
    }
}

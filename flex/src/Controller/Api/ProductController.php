<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValue;
use App\Entity\Unit;
use App\Repository\AbstractRepository;
use App\Service\ProductVariantGenerator;
use App\Service\StockProcessor;
use function join;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use function sprintf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductController
 * @package App\Controller\Api
 *
 * @Route("/api/product")
 */
class ProductController extends ApiController
{
    /**
     * List user products
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  },
     *  filters={
     *      {"name"="limit", "dataType"="integer", "description"="Limit results per page for paginated queries, default=100"}
     *  }
     * )
     *
     * @Route("", defaults={"page": "0"}, name="product_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="product_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $products = $this->getDoctrine()->getRepository(Product::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit    = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $products = $this->getDoctrine()->getRepository(Product::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $products,
        ]);
    }

    /**
     * Get product by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product is found"
     *  }
     * )
     *
     * @Route("/{id}", name="product_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id Product ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get product by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="product_get_external")
     * @Method("GET")
     *
     * @param string $id Product external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(Product::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create product
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="name", "dataType"="string", "required"=true, "description"="Product title"},
     *      {"name"="defaultUnitId", "dataType"="UUID", "required"=true, "description"="Product default unit ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product external ID"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Product description"},
     *      {"name"="variantSelectionMethod", "dataType"="string", "required"=false, "description"="Product variant selection method: 1) choice - A list of all variants is displayed to user. 2) match  - Each product option is displayed as select field. User selects the values and we match them to variant.", "pattern"="choice|match"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="product_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Product::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new Product();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

        $defaultUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('defaultUnitId'));
        if (!$defaultUnit) {
            return $this->errorResponseAction('Default Unit is not found or already deleted');
        }

        $entity->setDefaultUnit($defaultUnit);

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
     * Update Product
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="name", "dataType"="string", "required"=false, "description"="Product name"},
     *      {"name"="defaultUnitId", "dataType"="UUID", "required"=true, "description"="Product default unit ID"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product external ID"},
     *      {"name"="description", "dataType"="string", "required"=false, "description"="Product description"},
     *      {"name"="variantSelectionMethod", "dataType"="string", "required"=false, "description"="Product variant selection method: 1) choice - A list of all variants is displayed to user. 2) match  - Each product option is displayed as select field. User selects the values and we match them to variant.", "pattern"="choice|match", "pattern"="choice|match"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="product_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Product::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var Product $entity */
        $entity = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $entity->fillFromRequest($request);

        if ($request->request->has('defaultUnitId')) {
            $defaultUnit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('defaultUnitId'));
            $entity->setDefaultUnit($defaultUnit);
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
     * Delete Product
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product is found"
     *  }
     * )
     *
     * @Route("/delete", name="product_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Product::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
        if (!$product) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $em->remove($product);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Add Product Attribute Values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="list", "dataType"="array", "required"=true, "description"="List of Product Attribute Values IDs"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/add-attribute-values", name="product_add_attribute_values", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @return JsonResponse
     */
    public function addAttributeValuesAction(string $id, Request $request) : JsonResponse
    {
        return $this->processAttributeValuesList($id, $request, 'add');
    }

    /**
     * Remove Product Attribute Values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="list", "dataType"="array", "required"=true, "description"="List of Product Attribute Values IDs"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/remove-attribute-values", name="product_remove_attribute_values", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAttributeValuesAction(string $id, Request $request) : JsonResponse
    {
        return $this->processAttributeValuesList($id, $request, 'remove');
    }

    /**
     * @param string $id
     * @param Request $request
     * @param string $action
     * @return JsonResponse
     */
    private function processAttributeValuesList(string $id, Request $request, string $action) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Product::listExists($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var Product $entity */
        $entity = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $wrongAttributeValueIds = [];
        $valuesToProcess              = [];
        foreach ($request->request->get('list', []) as $attributeValueId) {
            /** @var ProductAttributeValue $attributeValue */
            $attributeValue = $em->getRepository(ProductAttributeValue::class)->findForUser($this->commonProcessor->getUserUUID(), $attributeValueId);
            if (!$attributeValue) {
                $wrongAttributeValueIds[] = $attributeValueId;
            } else {
                $valuesToProcess[] = $attributeValue;
            }
        }
        if ($wrongAttributeValueIds) {
            return $this->errorResponseAction(sprintf('Next attribute values can not be found %s', join('; ', $wrongAttributeValueIds)));
        }

        /** @var ProductAttributeValue $attributeValue */
        foreach ($valuesToProcess as $attributeValue) {
            switch ($action) {
                case 'add':
                    $entity->addAttributeValue($attributeValue);
                    break;

                case 'remove':
                    $entity->removeAttributeValue($attributeValue);
                    break;
            }
        }

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity->refreshDocument();
        $em->persist($entity);
        $em->flush();

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Generate Product Variants
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product is found"
     *  }
     * )
     *
     * @Route("/generate-variants", name="product_generate_variants")
     * @Method("POST")
     *
     * @param Request $request
     * @param ProductVariantGenerator $variantGenerator
     * @return JsonResponse
     */
    public function generateVariantsAction(Request $request, ProductVariantGenerator $variantGenerator) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Product::generateVariants($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productId'));
        if (!$product) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $variantGenerator->generate($product);

        $em->flush();

        return $this->responseAction([
            'objects' => $product,
        ]);
    }

    /**
     * Get Product balance by ID based on Unit ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="unitId", "dataType"="UUID", "required"=false, "description"="Product unit ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/balance", name="product_balance", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function getBalanceAction(string $id, Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $errors = \App\Validator\Api\Product::getBalance($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$product) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        if ($stockProcessor->checkMissingUnitEquals($product)) {
            return $this->errorResponseAction('Not all unit equals are set. Please set missed unit equals');
        }

        $value = $stockProcessor->getProductBalance($product, $unit);

        return $this->responseAction([
            'data' => $value,
        ]);
    }
}

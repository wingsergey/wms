<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductOptionValue;
use App\Entity\ProductVariant;
use App\Entity\Unit;
use App\Repository\AbstractRepository;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductVariantController
 * @package App\Controller\Api
 *
 * @Route("/api/product-variant")
 */
class ProductVariantController extends ApiController
{
    /**
     * List user product variants
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
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
     * @Route("", defaults={"page": "0"}, name="product_variant_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="product_variant_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $productVariants = $this->getDoctrine()->getRepository(ProductVariant::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $productVariants = $this->getDoctrine()->getRepository(ProductVariant::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $productVariants,
        ]);
    }

    /**
     * Get product variant by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product variant is found"
     *  }
     * )
     *
     * @Route("/{id}", name="product_variant_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id ProductVariant ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get product variant by external ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product variant is found"
     *  }
     * )
     *
     * @Route("/{id}/external", name="product_variant_get_external")
     * @Method("GET")
     *
     * @param string $id ProductVariant external ID
     * @return JsonResponse
     */
    public function getExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(ProductVariant::class)->findByExternalId($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Create product variant
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"},
     *      {"name"="name", "dataType"="string", "required"=false, "description"="Product variant title"},
     *      {"name"="sku", "dataType"="string", "required"=false, "description"="Product variant sku"},
     *      {"name"="master", "dataType"="bool", "required"=false, "description"="Is Product master variant"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product variant external ID"},
     *      {"name"="price", "dataType"="integer", "required"=false, "description"="Product variant price"},
     *      {"name"="width", "dataType"="float", "required"=false, "description"="Product variant width"},
     *      {"name"="height", "dataType"="float", "required"=false, "description"="Product variant height"},
     *      {"name"="depth", "dataType"="float", "required"=false, "description"="Product variant depth"},
     *      {"name"="weight", "dataType"="float", "required"=false, "description"="Product variant weight"}
     *  },
     *  statusCodes={
     *      201="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/create", name="product_variant_create")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductVariant::create($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new ProductVariant();
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
     * Update Product variant
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productId", "dataType"="UUID", "required"=true, "description"="Product ID"},
     *      {"name"="name", "dataType"="string", "required"=false, "description"="Product variant title"},
     *      {"name"="sku", "dataType"="string", "required"=false, "description"="Product variant sku"},
     *      {"name"="master", "dataType"="bool", "required"=false, "description"="Is Product master variant"},
     *      {"name"="externalId", "dataType"="string", "required"=false, "description"="Product variant external ID"},
     *      {"name"="price", "dataType"="integer", "required"=false, "description"="Product variant price"},
     *      {"name"="width", "dataType"="float", "required"=false, "description"="Product variant width"},
     *      {"name"="height", "dataType"="float", "required"=false, "description"="Product variant height"},
     *      {"name"="depth", "dataType"="float", "required"=false, "description"="Product variant depth"},
     *      {"name"="weight", "dataType"="float", "required"=false, "description"="Product variant weight"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}", name="product_variant_update", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id ProductVariant ID
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(string $id, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductVariant::update($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product variant is not found or already deleted');
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
     * Delete Product variant
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="productVariantId", "dataType"="UUID", "required"=true, "description"="Product ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Product is found"
     *  }
     * )
     *
     * @Route("/delete", name="product_variant_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductVariant::delete($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var ProductVariant $productVariant */
        $productVariant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('productVariantId'));

        if (!$productVariant) {
            return $this->errorResponseAction('Product variant is not found or already deleted');
        }

        if ($productVariant->getMaster()) {
            return $this->errorResponseAction('Product variant is master and can not be deleted');
        }

        $em->remove($productVariant);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Add Product Variant Option Values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="list", "dataType"="array", "required"=true, "description"="List of Product Variant Option Values IDs"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/add-option-values", name="product_variant_add_option_values", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @return JsonResponse
     */
    public function addOptionValuesAction(string $id, Request $request) : JsonResponse
    {
        return $this->processOptionValuesList($id, $request, 'add');
    }

    /**
     * Remove Product Variant Option Values
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="list", "dataType"="array", "required"=true, "description"="List of Product Variant Option Values IDs"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/remove-option-values", name="product_variant_remove_option_values", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id Product ID
     * @param Request $request
     * @return JsonResponse
     */
    public function removeOptionValuesAction(string $id, Request $request) : JsonResponse
    {
        return $this->processOptionValuesList($id, $request, 'remove');
    }

    /**
     * @param string $id
     * @param Request $request
     * @param string $action
     * @return JsonResponse
     */
    private function processOptionValuesList(string $id, Request $request, string $action) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\ProductVariant::listExists($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var ProductVariant $entity */
        $entity = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$entity) {
            return $this->errorResponseAction('Product is not found or already deleted');
        }

        $wrongOptionValueIds = [];
        $valuesToProcess              = [];
        foreach ($request->request->get('list', []) as $optionValueId) {
            /** @var ProductOptionValue $optionValue */
            $optionValue = $em->getRepository(ProductOptionValue::class)->findForUser($this->commonProcessor->getUserUUID(), $optionValueId);
            if (!$optionValue) {
                $wrongOptionValueIds[] = $optionValueId;
            } else {
                $valuesToProcess[] = $optionValue;
            }
        }
        if ($wrongOptionValueIds) {
            return $this->errorResponseAction(sprintf('Next option values can not be found %s', join('; ', $wrongOptionValueIds)));
        }

        /** @var ProductOptionValue $optionValue */
        foreach ($valuesToProcess as $optionValue) {
            switch ($action) {
                case 'add':
                    $entity->addOptionValue($optionValue);
                    break;

                case 'remove':
                    $entity->removeOptionValue($optionValue);
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
     * Get Product variant balance by ID based on Unit ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="unitId", "dataType"="UUID", "required"=false, "description"="Product variant unit ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/balance", name="product_variant_balance", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id ProductVariant ID
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function getBalanceAction(string $id, Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $errors = \App\Validator\Api\ProductVariant::getBalance($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em = $this->getDoctrine()->getManager();
        /** @var ProductVariant $productVariant */
        $productVariant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$productVariant) {
            return $this->errorResponseAction('Product variant is not found or already deleted');
        }

        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        if ($stockProcessor->checkMissingUnitEquals($productVariant->getProduct())) {
            return $this->errorResponseAction('Not all unit equals are set. Please set missed unit equals');
        }

        $value = $stockProcessor->getProductVariantBalance($productVariant, $unit);

        return $this->responseAction([
            'data' => $value,
        ]);
    }

    /**
     * Get varaint revisions map
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Product variant",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/{id}/revision-map", name="product_variant_revision_map", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id ProductVariant ID
     * @return JsonResponse
     */
    public function getRevisionMapAction(string $id) : JsonResponse
    {
        /** @var ProductVariant $entity */
        $entity = $this->getDoctrine()->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        if (!$entity) {
            return $this->errorResponseAction('Product variant is not found or already deleted');
        }

        return $this->responseAction([
            'objects' => $entity->getStockRevisions(),
        ]);
    }
}

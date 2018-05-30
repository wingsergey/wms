<?php

namespace App\Controller\Api;

use App\Entity\ProductVariant;
use App\Entity\StockHistory;
use App\Entity\Unit;
use App\Entity\Warehouse;
use App\Entity\WarehouseZone;
use App\Repository\AbstractRepository;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StockHistoryController
 * @package App\Controller\Api
 *
 * @Route("/api/stock-history")
 */
class StockHistoryController extends ApiController
{
    /**
     * Get stock history by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no stock history is found"
     *  }
     * )
     *
     * @Route("/{id}", name="stock_history_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id StockHistory ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * List stock histories by external item ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/list/{id}/external", name="stock_histories_list_external")
     * @Method("GET")
     *
     * @param string $id StockHistory External Item ID
     * @return JsonResponse
     */
    public function listExternalAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(StockHistory::class)->findByForUser($this->commonProcessor->getUserUUID(), [
            'externalItemId' => $id,
        ]);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * List all users stock histories
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
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
     * @Route("", name="stock_histories_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="stock_histories_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        if (!$page) {
            $stockHistories = $this->getDoctrine()->getRepository(StockHistory::class)->findAllForUser($this->commonProcessor->getUserUUID());
        } else {
            $limit = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $stockHistories = $this->getDoctrine()->getRepository(StockHistory::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), [], [], $page, $limit);
        }

        return $this->responseAction([
            'objects' => $stockHistories,
        ]);
    }

    /**
     * Delete Stock history
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="stockHistoryId", "dataType"="UUID", "required"=true, "description"="Stock ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/delete", name="stock_history_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function deleteAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::deleteHistory($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var StockHistory $stock */
        $stock = $em->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('stockHistoryId'));

        if (!$stock) {
            return $this->errorResponseAction('Stock history is not found or already deleted');
        }

        $stockProcessor->revertStock($stock);

        return $this->responseAction();
    }

    /**
     * Add product to stock
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="variantId", "dataType"="UUID", "required"=true, "description"="Product Variant ID added'"},
     *      {"name"="warehouseId", "dataType"="UUID", "required"=false, "description"="Warehouse ID where product placed"},
     *      {"name"="warehouseZoneId", "dataType"="UUID", "required"=true, "description"="Warehouse Zone ID where product placed"},
     *      {"name"="unitId", "dataType"="UUID", "required"=true, "description"="Unit id added"},
     *      {"name"="originStockHistoryId", "dataType"="UUID", "required"=false, "description"="Origin Stock History ID to add to"},
     *      {"name"="externalItemId", "dataType"="string", "required"=false, "description"="Order Item ID, max length 255 chars. If specified and already exist records with same ID only difference will be deducted."},
     *      {"name"="externalCartId", "dataType"="string", "required"=false, "description"="User Cart ID, max length 255 chars"},
     *      {"name"="changes", "dataType"="float", "required"=true, "description"="Changes qty"},
     *      {"name"="price", "dataType"="integer", "default"="0", "required"=false, "description"="Price in cents"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Order(s) is(are) found"
     *  }
     * )
     *
     * @Route("", name="stock_add_product")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function addProductToStockAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::addProductToStock($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = new StockHistory();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());

        $variant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('variantId'));
        $unit    = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        $entity->setVariant($variant);
        $entity->setUnit($unit);

        if ($request->request->has('warehouseZoneId')) {
            /** @var WarehouseZone $warehouseZone */
            $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseZoneId'));
            $entity->setWarehouseZone($warehouseZone);

            $warehouse = $warehouseZone->getWarehouse();
        } else {
            $warehouse = $em->getRepository(Warehouse::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseId'));
        }
        $entity->setWarehouse($warehouse);

        if ($request->request->has('originStockHistoryId')) {
            $originStockHistory = $em->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('originStockHistoryId'));
            $entity->setOriginStockHistory($originStockHistory);
        }

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $stockProcessor->processStockChanges($entity);

        if ($entity === null) {

            return $this->errorResponseAction('Stock for this item was already deducted with greater or equal qty');
        } elseif ($entity === false) {

            return $this->errorResponseAction('There is not enough items on stock');
        }

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Get stock history current balance by ID based on Unit ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock history",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="unitId", "dataType"="UUID", "required"=false, "description"="Unit ID, default Product Unit will be used if not specified"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/{id}/balance", name="stock_balance", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("POST")
     *
     * @param string $id StockHistory ID
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function getBalanceAction(string $id, Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $errors = \App\Validator\Api\Stock::getBalance($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $em = $this->getDoctrine()->getManager();
        /** @var StockHistory $stockHistory */
        $stockHistory = $this->getDoctrine()->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $id);
        if (!$stockHistory) {
            return $this->errorResponseAction('Stock is not found or already deleted');
        }

        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        if ($stockProcessor->checkMissingUnitEquals($stockHistory->getVariant()->getProduct())) {
            return $this->errorResponseAction('Not all unit equals are set in path. Please set missed unit equals for this product');
        }

        $value = $stockProcessor->getStockHistoryBalance($stockHistory, $unit);

        return $this->responseAction([
            'data' => $value,
        ]);
    }
}

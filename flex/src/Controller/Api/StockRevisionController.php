<?php

namespace App\Controller\Api;

use App\Entity\ProductVariant;
use App\Entity\StockHistory;
use App\Entity\StockRevision;
use App\Entity\Unit;
use App\Entity\WarehouseZone;
use App\Repository\AbstractRepository;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StockRevisionController
 * @package App\Controller\Api
 *
 * @Route("/api/stock-revision")
 */
class StockRevisionController extends ApiController
{
    /**
     * Get stock revision by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock revision",
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
     * @Route("/{id}", name="stock_revision_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id StockRevision ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(StockRevision::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * List stock revisions
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock revision",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  },
     *  filters={
     *      {"name"="limit", "dataType"="integer", "description"="Limit results per page for paginated queries, default=100"},
     *      {"name"="warehouseZoneId", "dataType"="UUID", "description"="Warehouse Zone ID"}
     *  }
     * )
     *
     * @Route("", name="stock_revision_list")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="stock_revision_list_paginated")
     * @Method("GET")
     *
     * @param int $page Page number
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(int $page = 0, Request $request) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $criteria = [];
        $orderBy = ['createdAt' => 'DESC'];

        /** @var WarehouseZone $warehouseZone */
        $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseZoneId'));
        if ($warehouseZone) {
            $criteria['warehouseZone'] = $warehouseZone;
        }

        if (!$page) {
            $revisions = $this->getDoctrine()->getRepository(StockRevision::class)->findByForUser($this->commonProcessor->getUserUUID(), $criteria, $orderBy);
        } else {
            $limit    = $request->query->get('limit', AbstractRepository::DEFAULT_PAGINATOR_LIMIT);
            $revisions = $this->getDoctrine()->getRepository(StockRevision::class)->findPaginatedForUser($this->commonProcessor->getUserUUID(), $criteria, $orderBy, $page, $limit);
        }

        return $this->responseAction([
            'objects' => $revisions,
        ]);
    }

    /**
     * Delete revision
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock revision",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="stockRevisionId", "dataType"="UUID", "required"=true, "description"="Stock revision ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/delete", name="stock_revision_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function deleteAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::deleteRevision($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var StockRevision $revision */
        $revision = $em->getRepository(StockRevision::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('stockRevisionId'));

        if (!$revision) {
            return $this->errorResponseAction('Stock revision is not found or already deleted');
        }

        $stockProcessor->deleteStockRevision($revision);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Add product revision on stock
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock revision",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="variantId", "dataType"="UUID", "required"=true, "description"="Product Variant ID"},
     *      {"name"="warehouseZoneId", "dataType"="UUID", "required"=true, "description"="Warehouse Zone ID"},
     *      {"name"="unitId", "dataType"="UUID", "required"=true, "description"="Unit ID"},
     *      {"name"="qty", "dataType"="float", "required"=true, "description"="Current stock qty"},
     *      {"name"="originStockHistoryId", "dataType"="UUID", "required"=false, "description"="Origin stock history ID"},
     *      {"name"="stockRevisionId", "dataType"="UUID", "required"=false, "description"="Existing Stock Revision ID"},
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Order(s) is(are) found"
     *  }
     * )
     *
     * @Route("/add", name="stock_add_revision")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function addRevisionAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::addStockRevision($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        $entity = $em->getRepository(StockRevision::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('stockRevisionId'));
        if (!$entity) {
            $entity = new StockRevision();
        }

        /** @var ProductVariant $variant */
        $variant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('variantId'));
        /** @var Unit $unit */
        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));
        /** @var WarehouseZone $warehouseZone */
        $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseZoneId'));
        /** @var StockHistory $originStockHistory */
        $originStockHistory = $em->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('originStockHistoryId'));
        if ($originStockHistory) {
            $entity->setOriginStockHistory($originStockHistory);
        }

        $entity->fillFromRequest($request);

        $entity->setUserId($this->commonProcessor->getUserUUID());
        $entity->setVariant($variant);
        $entity->setUnit($unit);
        $entity->setWarehouseZone($warehouseZone);

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        try {
            $entity = $stockProcessor->addRevision($entity);
        } catch (\Exception $exception) {
            return $this->errorResponseAction($exception->getMessage());
        }

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * Plan product revision on stock
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock revision",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="variantId", "dataType"="UUID", "required"=true, "description"="Product Variant ID"},
     *      {"name"="warehouseZoneId", "dataType"="UUID", "required"=true, "description"="Warehouse Zone ID"},
     *      {"name"="originStockHistoryId", "dataType"="UUID", "required"=false, "description"="Origin stock history ID"},
     *      {"name"="plannedOnDateTimestamp", "dataType"="string", "required"=true, "description"="Timestamp in future when next revision is planned"},
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Order(s) is(are) found"
     *  }
     * )
     *
     * @Route("/plan", name="stock_plan_revision")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function planRevisionAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::planStockRevision($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var ProductVariant $variant */
        $variant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('variantId'));

        /** @var WarehouseZone $warehouseZone */
        $warehouseZone = $em->getRepository(WarehouseZone::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('warehouseZoneId'));

        // check if revision already planned for this variant at this zone
        $entity = $em->getRepository(StockRevision::class)->getFirstPlannedVariantRevision($variant, $warehouseZone);
        if (!$entity) {
            $entity = new StockRevision();
        }

        /** @var StockHistory $originStockHistory */
        $originStockHistory = $em->getRepository(StockHistory::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('originStockHistoryId'));
        if ($originStockHistory) {
            $entity->setOriginStockHistory($originStockHistory);
        }

        $plannedOnDate = null;
        if ($request->request->has('plannedOnDateTimestamp')) {
            $plannedOnDate = new \DateTime('@' . (string) $request->request->get('plannedOnDateTimestamp'));
        }

        $entity->fillFromRequest($request);

        $entity->setPlannedOnDate($plannedOnDate);
        $entity->setUserId($this->commonProcessor->getUserUUID());
        $entity->setVariant($variant);
        $entity->setWarehouseZone($warehouseZone);

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        try {
            $entity = $stockProcessor->planRevision($entity);
        } catch (\Exception $exception) {
            return $this->errorResponseAction($exception->getMessage());
        }

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\ProductVariant;
use App\Entity\StockReservation;
use App\Entity\Unit;
use App\Service\StockProcessor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StockReservationController
 * @package App\Controller\Api
 *
 * @Route("/api/stock-reservation")
 */
class StockReservationController extends ApiController
{
    /**
     * Get stock reserve by ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock reservation",
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
     * @Route("/{id}", name="stock_reservation_get", requirements={"id": "([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}"})
     * @Method("GET")
     *
     * @param string $id StockReservation ID
     * @return JsonResponse
     */
    public function getAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(StockReservation::class)->findForUser($this->commonProcessor->getUserUUID(), $id);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * List stock reserves by external cart ID
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock reservation",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("/list/{id}/external-cart", name="stock_reserves_list_external_cart")
     * @Method("GET")
     *
     * @param string $id StockReservation External ID
     * @return JsonResponse
     */
    public function listExternalCartAction(string $id) : JsonResponse
    {
        $entity = $this->getDoctrine()->getRepository(StockReservation::class)->findByForUser($this->commonProcessor->getUserUUID(), [
            'externalCartId' => $id,
        ]);

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }

    /**
     * List stock reserves
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock reservation",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed"
     *  }
     * )
     *
     * @Route("", name="stock_reserves_list")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function listAction() : JsonResponse
    {
        $warehouses = $this->getDoctrine()->getRepository(StockReservation::class)->findAllForUser($this->commonProcessor->getUserUUID());

        return $this->responseAction([
            'objects' => $warehouses,
        ]);
    }

    /**
     * Delete reserve
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock reservation",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="stockReservationId", "dataType"="UUID", "required"=true, "description"="Stock ID"}
     *  },
     *  statusCodes={
     *      200="Returned when successful",
     *      401="Returned when User ID is not passed",
     *      404="Returned when no Warehouse is found"
     *  }
     * )
     *
     * @Route("/delete", name="stock_reserve_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function deleteAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::deleteReserve($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        /** @var StockReservation $reserve */
        $reserve = $em->getRepository(StockReservation::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('stockReservationId'));

        if (!$reserve) {
            return $this->errorResponseAction('Stock reserve is not found or already deleted');
        }

        $stockProcessor->deleteStockReservation($reserve);
        $em->flush();

        return $this->responseAction();
    }

    /**
     * Reserve product on stock
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Stock reservation",
     *  headers={
     *      {"name"="User-ID", "required"=true, "description"="User UUID"}
     *  },
     *  parameters={
     *      {"name"="variantId", "dataType"="UUID", "required"=true, "description"="Product Variant ID added"},
     *      {"name"="unitId", "dataType"="UUID", "required"=true, "description"="Unit id added"},
     *      {"name"="externalCartId", "dataType"="string", "required"=true, "description"="User Cart ID, max length 255 chars"},
     *      {"name"="expirationDateTimestamp", "dataType"="string", "required"=false, "description"="Timestamp to override reserve expiration date. Default value is in 15 min"},
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
     * @Route("/reserve-product", name="stock_reserve_product")
     * @Method("POST")
     *
     * @param Request $request
     * @param StockProcessor $stockProcessor
     * @return JsonResponse
     */
    public function reserveProductOnStockAction(Request $request, StockProcessor $stockProcessor) : JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $errors = \App\Validator\Api\Stock::reserveProductOnStock($request);
        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }
        /** @var ProductVariant $variant */
        $variant = $em->getRepository(ProductVariant::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('variantId'));
        /** @var Unit $unit */
        $unit = $em->getRepository(Unit::class)->findForUser($this->commonProcessor->getUserUUID(), $request->request->get('unitId'));

        $expirationDate = null;
        if ($request->request->has('expirationDateTimestamp')) {
            $expirationDate = new \DateTime('@' . (string) $request->request->get('expirationDateTimestamp'));
        }

        $entity = new StockReservation();
        $entity->fillFromRequest($request);
        $entity->setUserId($this->commonProcessor->getUserUUID());
        $entity->setVariant($variant);
        $entity->setUnit($unit);
        $entity->setPrice($request->request->get('price', 0));
        $entity->setExpirationDate($expirationDate);
        $entity->setExternalCartId($request->request->get('externalCartId', null));
        $entity->setChanges($request->request->get('changes'));

        $validator = $this->get('validator');
        $errors    = $validator->validate($entity);

        if (count($errors) > 0) {
            return $this->violationFailedResponseAction($errors);
        }

        try {
            $entity = $stockProcessor->reserveVariantOnStock($entity);
        } catch (\Exception $exception) {
            return $this->errorResponseAction($exception->getMessage());
        }

        if ($entity === null) {

            return $this->errorResponseAction('Stock for this cart was already reserved with greater or equal qty');
        } elseif ($entity === false) {

            return $this->errorResponseAction('There is not enough items on stock');
        }

        return $this->responseAction([
            'objects' => $entity,
        ]);
    }
}

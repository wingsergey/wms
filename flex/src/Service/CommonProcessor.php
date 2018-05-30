<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CommonProcessor
 * @package App\Service
 */
class CommonProcessor
{
    use LoggerAwareTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public $userUUID;

    /**
     * CommonProcessor constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     */
    public function checkUserID(Request $request)
    {
        $userID = $request->headers->get('User-ID');

        $this->userUUID = Uuid::fromString($userID);
    }

    /**
     * @return mixed
     */
    public function getUserUUID()
    {
        return $this->userUUID;
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $str
     * @param bool $stripTags
     * @return string
     */
    function sanitizeInputs($str, $stripTags = false)
    {
        $str = trim($str);

        if ($stripTags) {
            $str = strip_tags($str);
        }

        $str = htmlspecialchars($str);

        return $str;
    }

    /**
     * @param $number
     * @param  int $decimals
     * @param  string $decPoint
     * @param  string $thousandsSep
     * @return string
     */
    public static function priceFilter($number, $decimals = 2, $decPoint = '.', $thousandsSep = ' ')
    {
        $number = round($number / 100, 2);
        $price  = number_format($number, $decimals, $decPoint, $thousandsSep);

        return $price;
    }

    /**
     * @param $int_price
     * @return int
     */
    public static function roundIncomePrice($int_price)
    {
        if (is_string($int_price)) {
            $int_price = (float) $int_price;
        }
        $int_price = $int_price / 100;
        $int_price = round($int_price);
        $int_price = $int_price * 100;

        return (int) $int_price;
    }

    /**
     * @param $price
     * @return int
     */
    public static function convertPriceToInt($price)
    {
        if (is_string($price) && strpos($price, '.') !== false) {
            $price = (float) $price;
        }

        if (is_float($price)) {
            $price *= 100;
        }

        $int_price = round($price);

        return (int) $int_price;
    }
}

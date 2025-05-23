<?php

namespace IAWPSCOPED\IPLib\Range;

use IAWPSCOPED\IPLib\Address\AddressInterface;
use IAWPSCOPED\IPLib\Address\IPv4;
use IAWPSCOPED\IPLib\Address\IPv6;
use IAWPSCOPED\IPLib\Address\Type as AddressType;
use IAWPSCOPED\IPLib\Factory;
/**
 * Base class for range classes.
 * @internal
 */
abstract class AbstractRange implements RangeInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getRangeType()
     */
    public function getRangeType()
    {
        if ($this->rangeType === null) {
            $addressType = $this->getAddressType();
            if ($addressType === AddressType::T_IPv6 && Subnet::get6to4()->containsRange($this)) {
                $this->rangeType = Factory::getRangeFromBoundaries($this->fromAddress->toIPv4(), $this->toAddress->toIPv4())->getRangeType();
            } else {
                switch ($addressType) {
                    case AddressType::T_IPv4:
                        $defaultType = IPv4::getDefaultReservedRangeType();
                        $reservedRanges = IPv4::getReservedRanges();
                        break;
                    case AddressType::T_IPv6:
                        $defaultType = IPv6::getDefaultReservedRangeType();
                        $reservedRanges = IPv6::getReservedRanges();
                        break;
                    default:
                        throw new \Exception('@todo');
                }
                $rangeType = null;
                foreach ($reservedRanges as $reservedRange) {
                    $rangeType = $reservedRange->getRangeType($this);
                    if ($rangeType !== null) {
                        break;
                    }
                }
                $this->rangeType = $rangeType === null ? $defaultType : $rangeType;
            }
        }
        return $this->rangeType === \false ? null : $this->rangeType;
    }
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::getAddressAtOffset()
     */
    public function getAddressAtOffset($n)
    {
        if (!\is_int($n)) {
            return null;
        }
        $address = null;
        if ($n >= 0) {
            $start = Factory::parseAddressString($this->getComparableStartString());
            $address = $start->getAddressAtOffset($n);
        } else {
            $end = Factory::parseAddressString($this->getComparableEndString());
            $address = $end->getAddressAtOffset($n + 1);
        }
        if ($address === null) {
            return null;
        }
        return $this->contains($address) ? $address : null;
    }
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::contains()
     */
    public function contains(AddressInterface $address)
    {
        $result = \false;
        if ($address->getAddressType() === $this->getAddressType()) {
            $cmp = $address->getComparableString();
            $from = $this->getComparableStartString();
            if ($cmp >= $from) {
                $to = $this->getComparableEndString();
                if ($cmp <= $to) {
                    $result = \true;
                }
            }
        }
        return $result;
    }
    /**
     * {@inheritdoc}
     *
     * @see \IPLib\Range\RangeInterface::containsRange()
     */
    public function containsRange(RangeInterface $range)
    {
        $result = \false;
        if ($range->getAddressType() === $this->getAddressType()) {
            $myStart = $this->getComparableStartString();
            $itsStart = $range->getComparableStartString();
            if ($itsStart >= $myStart) {
                $myEnd = $this->getComparableEndString();
                $itsEnd = $range->getComparableEndString();
                if ($itsEnd <= $myEnd) {
                    $result = \true;
                }
            }
        }
        return $result;
    }
}

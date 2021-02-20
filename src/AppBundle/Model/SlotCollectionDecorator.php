<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Decorator for a collection of SlotInterface
 */
class SlotCollectionDecorator implements \AppBundle\Model\SlotCollectionInterface
{
    protected $slots;

    public function __construct(\Doctrine\Common\Collections\Collection $slots)
    {
        $this->slots = $slots;
    }

    public function add($element)
    {
        return $this->slots->add($element);
    }

    public function removeElement($element)
    {
        return $this->slots->removeElement($element);
    }

    public function count($mode = null)
    {
        return $this->slots->count($mode);
    }

    public function getIterator()
    {
        return $this->slots->getIterator();
    }

    public function offsetExists($offset)
    {
        return $this->slots->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->slots->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->slots->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->slots->offsetUnset($offset);
    }

    public function countCards()
    {
        $count = 0;
        foreach ($this->slots as $slot) {
            $count += $slot->getQuantity();
        }
        return $count;
    }

    public function getIncludedSets()
    {
        $sets = [];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            $set = $card->getSet();
            if (!isset($sets[$set->getPosition()])) {
                $sets[$set->getPosition()] = [
                    'set' => $set,
                    'nb' => 0
                ];
            }
        }
        ksort($sets);
        return array_values($sets);
    }

    public function getSlotsByType()
    {
        $slotsByType = ['admirals-order' => [],
                        'character' => [],
                        'creature' => [],
                        'device' => [],
                        'defensive-shield' => [],
                        'effect' => [],
                        'epic-event' => [],
                        'interrupt' => [],
                        'jedi-test' => [],
                        'location' => [],
                        'objective' => [],
                        'podracer' => [],
                        'starship' => [],
                        'vehicle' => [],
                        'weapon' => []];
        foreach ($this->slots as $slot) {
            if (array_key_exists($slot->getCard()->getType()->getCode(), $slotsByType)) {
                $slotsByType[$slot->getCard()->getType()->getCode()][] = $slot;
            }
        }
        return $slotsByType;
    }

    public function getCountByType()
    {
      $countByType = ['admirals-order' => 0,
                      'character' => 0,
                      'creature' => 0,
                      'device' => 0,
                      'effect' => 0,
                      'epic-event' => 0,
                      'interrupt' => 0,
                      'jedi-test' => 0,
                      'location' => 0,
                      'objective' => 0,
                      'podracer' => 0,
                      'starship' => 0,
                      'vehicle' => 0,
                      'weapon' => 0];
        foreach ($this->slots as $slot) {
            if (array_key_exists($slot->getCard()->getType()->getCode(), $countByType)) {
                $countByType[$slot->getCard()->getType()->getCode()] += $slot->getQuantity();
            }
        }
        return $countByType;
    }

    public function getDrawDeck()
    {
        $drawDeck = [];
        foreach ($this->slots as $slot) {
          $drawDeck[] = $slot;
        }
        return new SlotCollectionDecorator(new ArrayCollection($drawDeck));
    }

    public function filterBySide($side_code)
    {
        $slots = [];
        foreach ($this->slots as $slot) {
            if ($slot->getCard()->getSide()->getCode() === $side_code) {
                $slots[] = $slot;
            }
        }
        return new SlotCollectionDecorator(new ArrayCollection($slots));
    }

    public function filterByType($type_code)
    {
        $slots = [];
        foreach ($this->slots as $slot) {
            if ($slot->getCard()->getType()->getCode() === $type_code) {
                $slots[] = $slot;
            }
        }
        return new SlotCollectionDecorator(new ArrayCollection($slots));
    }

    public function getCopiesAndDeckLimit()
    {
        $copiesAndDeckLimit = [];
        foreach ($this->slots as $slot) {
            $cardName = $slot->getCard()->getName();
            if (!key_exists($cardName, $copiesAndDeckLimit)) {
                $copiesAndDeckLimit[$cardName] = [
                    'copies' => $slot->getQuantity(),
                    'deck_limit' => $slot->getCard()->getDeckLimit(),
                ];
            } else {
                $copiesAndDeckLimit[$cardName]['copies'] += $slot->getQuantity();
                $copiesAndDeckLimit[$cardName]['deck_limit'] = min($slot->getCard()->getDeckLimit(), $copiesAndDeckLimit[$cardName]['deck_limit']);
            }
        }
        return $copiesAndDeckLimit;
    }

    public function getSlots()
    {
        return $this->slots;
    }

    public function getContent()
    {
        $arr = array();
        foreach ($this->slots as $slot) {
            $arr [$slot->getCard()->getCode()] = $slot->getQuantity();
        }
        ksort($arr);
        return $arr;
    }
}

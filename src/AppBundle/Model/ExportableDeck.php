<?php

namespace AppBundle\Model;
use \AppBundle\Services\CardFormatter;

class ExportableDeck
{
    public function getArrayExport($withUnsavedChanges = false)
    {
        $slots = $this->getSlots();
        $array = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'date_creation' => $this->getDateCreation()->format('c'),
            'date_update' => $this->getDateUpdate()->format('c'),
            'description_md' => $this->getDescriptionMd(),
            'user_id' => $this->getUser()->getId(),
            'side_code' => $this->getSide()->getCode(),
            'side_name' => $this->getSide()->getName(),
            'objective' => $this->getObjective(),
            'slots' => $slots->getContent(),
            'version' => $this->getVersion(),
        ];

        return $array;
    }

    public function getTextExport()
    {
        $slots = $this->getSlots();
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'side' => $this->getSide(),
            'draw_deck_size' => $slots->getDrawDeck()->countCards(),
            'included_sets' => $slots->getIncludedSets(),
            'slots_by_type' => $slots->getSlotsByType()
        ];
    }
}

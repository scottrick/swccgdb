<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Card;
use AppBundle\Entity\Set;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Decklist;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Criteria;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiController extends Controller
{

    /**
     * Get the description of all the sets as an array of JSON objects.
     *
     *
     * @ApiDoc(
     *  section="Set",
     *  resource=true,
     *  description="All the Sets",
     *  parameters={
     *    {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     * )
     *
     * @param Request $request
     */
    public function listSetsAction(Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array(
            'Access-Control-Allow-Origin' => '*',
            'Content-Language' => $request->getLocale()
        ));

        $jsonp = $request->query->get('jsonp');

        $list_sets = $this->getDoctrine()->getRepository('AppBundle:Set')->findAll();

        // check the last-modified-since header

        $lastModified = null;
        /* @var $set \AppBundle\Entity\Set */
        foreach ($list_sets as $set) {
            if (!$lastModified || $lastModified < $set->getDateUpdate()) {
                $lastModified = $set->getDateUpdate();
            }
        }
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response

        $sets = array();
        /* @var $set \AppBundle\Entity\Set */
        foreach ($list_sets as $set) {
            $real = count($set->getCards());
            $max = $set->getSize();
            $sets[] = array(
                "name" => $set->getName(),
                "code" => $set->getCode(),
                "position" => $set->getPosition(),
                "cycle_position" => $set->getCycle()->getPosition(),
                "available" => $set->getDateRelease() ? $set->getDateRelease()->format('Y-m-d') : '',
                "known" => intval($real),
                "total" => $max,
                "url" => $this->get('router')->generate('cards_list', array('set_code' => $set->getCode()), UrlGeneratorInterface::ABSOLUTE_URL),
            );
        }

        $content = json_encode($sets);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        return $response;
    }

    /**
     * Get the description of a card as a JSON object.
     *
     * @ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="One Card",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="card_code",
     *          "dataType"="string",
     *          "description"="The code of the card to get, e.g. '01001'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @param Request $request
     */
    public function getCardAction($card_code, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array(
            'Access-Control-Allow-Origin' => '*',
            'Content-Language' => $request->getLocale()
        ));

        $jsonp = $request->query->get('jsonp');

        $card = $this->getDoctrine()->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));
        if (!$card instanceof Card) {
            return $this->createNotFoundException();
        }

        // check the last-modified-since header

        $lastModified = null;
        /* @var $card \AppBundle\Entity\Card */
        if (!$lastModified || $lastModified < $card->getDateUpdate()) {
            $lastModified = $card->getDateUpdate();
        }
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response

        /* @var $card \AppBundle\Entity\Card */
        $card = $this->get('card_formatter')->getCardInfo($card, true, "en");

        $content = json_encode($card);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        return $response;
    }

    /**
     * Get the description of all the cards as an array of JSON objects.
     *
     * @ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="All the Cards",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     * )
     *
     * @param Request $request
     */
    public function listCardsAction(Request $request)
    {
        $locale = $request->getLocale();

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array(
            'Access-Control-Allow-Origin' => '*',
            'Content-Language' => $locale
        ));

        $jsonp = $request->query->get('jsonp');

        $list_cards = $this->getDoctrine()->getRepository('AppBundle:Card')->findAll();
        // check the last-modified-since header

        $lastModified = null;
        /* @var $card \AppBundle\Entity\Card */
        foreach ($list_cards as $card) {
            if (!$lastModified || $lastModified < $card->getDateUpdate()) {
                $lastModified = $card->getDateUpdate();
            }
        }
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        // build the response

        $cards = array();
        /* @var $card \AppBundle\Entity\Card */
        foreach ($list_cards as $card) {
            $cards[] = $this->get('card_formatter')->getCardInfo($card, true, $locale);
        }

        $content = json_encode($cards);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        return $response;
    }

    /**
     * Get the description of all the card from a set, as an array of JSON objects.
     *
     * @ApiDoc(
     *  section="Card",
     *  resource=true,
     *  description="All the Cards from One Set",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="set_code",
     *          "dataType"="string",
     *          "description"="The code of the set to get the cards from, e.g. 'Core'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json|xml|xlsx|xls",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @param Request $request
     */
    public function listCardsBySetAction($set_code, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array('Access-Control-Allow-Origin' => '*'));

        $jsonp = $request->query->get('jsonp');

        $format = $request->getRequestFormat();
        if ($format !== 'json') {
            $response->setContent($request->getRequestFormat() . ' format not supported. Only json is supported.');
            return $response;
        }

        $set = $this->getDoctrine()->getRepository('AppBundle:Set')->findOneBy(array('code' => $set_code));
        if (!$set instanceof Set) {
            return $this->createNotFoundException();
        }

        $conditions = $this->get('cards_data')->syntax("e:$set_code");
        $this->get('cards_data')->validateConditions($conditions);
        $query = $this->get('cards_data')->buildQueryFromConditions($conditions);

        $cards = array();
        $last_modified = null;
        if ($query && $rows = $this->get('cards_data')->get_search_rows($conditions, "set")) {
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getDateUpdate()) {
                    $last_modified = $rows[$rowindex]->getDateUpdate();
                }
            }
            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                $card = $this->get('card_formatter')->getCardInfo($rows[$rowindex], true, "en");
                $cards[] = $card;
            }
        }

        $content = json_encode($cards);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);

        return $response;
    }

    /**
     * Get the description of a decklist as a JSON object.
     *
     * @ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="One Decklist",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="decklist_id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="The numeric identifier of the decklist"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @param Request $request
     */
    public function getDecklistAction($decklist_id, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array('Access-Control-Allow-Origin' => '*'));

        $jsonp = $request->query->get('jsonp');

        $format = $request->getRequestFormat();
        if ($format !== 'json') {
            $response->setContent($request->getRequestFormat() . ' format not supported. Only json is supported.');
            return $response;
        }

        /* @var $decklist \AppBundle\Entity\Decklist */
        $decklist = $this->getDoctrine()->getRepository('AppBundle:Decklist')->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            return $this->createNotFoundException();
        }

        $response->setLastModified($decklist->getDateUpdate());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = json_encode($decklist);

        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }

        $response->setContent($content);
        return $response;
    }

    /**
     * Get the description of all the decklists published at a given date, as an array of JSON objects.
     *
     * @ApiDoc(
     *  section="Decklist",
     *  resource=true,
     *  description="All the Decklists from One Day",
     *  parameters={
     *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
     *  },
     *  requirements={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "requirement"="\d\d\d\d-\d\d-\d\d",
     *          "description"="The date, format 'Y-m-d'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
     * )
     *
     * @param Request $request
     */
    public function listDecklistsByDateAction($date, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        $response->headers->add(array('Access-Control-Allow-Origin' => '*'));

        $jsonp = $request->query->get('jsonp');

        $format = $request->getRequestFormat();
        if ($format !== 'json') {
            $response->setContent($request->getRequestFormat() . ' format not supported. Only json is supported.');
            return $response;
        }

        $start = \DateTime::createFromFormat('Y-m-d', $date);
        $start->setTime(0, 0, 0);
        $end = clone $start;
        $end->add(new \DateInterval("P1D"));

        $expr = Criteria::expr();
        $criteria = Criteria::create();
        $criteria->where($expr->gte('dateCreation', $start));
        $criteria->andWhere($expr->lt('dateCreation', $end));

        /* @var $decklists \Doctrine\Common\Collections\ArrayCollection */
        $decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->matching($criteria);
        if (!$decklists) {
            die();
        }

        $dateUpdates = $decklists->map(function ($decklist) {
            return $decklist->getDateUpdate();
        })->toArray();

        $response->setLastModified(max($dateUpdates));
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = json_encode($decklists->toArray());

        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }

        $response->setContent($content);
        return $response;
    }
}

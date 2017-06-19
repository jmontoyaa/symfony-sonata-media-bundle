<?php

namespace MediaMonks\SonataMediaBundle\Controller;

use MediaMonks\SonataMediaBundle\Admin\MediaAdmin;
use MediaMonks\SonataMediaBundle\Model\MediaInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class HelperController
{
    /**
     * @var MediaAdmin
     */
    private $mediaAdmin;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @param MediaAdmin $mediaAdmin
     * @param EngineInterface $templating
     */
    public function __construct(MediaAdmin $mediaAdmin, EngineInterface $templating)
    {
        $this->mediaAdmin = $mediaAdmin;
        $this->templating = $templating;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAutocompleteItemsAction(Request $request)
    {
        $this->mediaAdmin->checkAccess('list');

        $minimumInputLength = 3;
        $searchText = $request->get('q');
        if (mb_strlen($searchText, 'UTF-8') < $minimumInputLength) {
            return new JsonResponse(['status' => 'KO', 'message' => 'Too short search string'], Response::HTTP_FORBIDDEN);
        }

        $this->mediaAdmin->setPersistFilters(false);
        $datagrid = $this->mediaAdmin->getDatagrid();
        $datagrid->setValue('title', null, $searchText);
        $datagrid->setValue('_per_page', null, $request->query->get('_per_page', 10));
        $datagrid->setValue('_page', null, $request->query->get('_page', 1));
        if ($request->query->has('type')) {
            $datagrid->setValue('type', null, $request->query->get('type'));
        }
        if ($request->query->has('provider')) {
            $datagrid->setValue('provider', null, $request->query->get('provider'));
        }
        $datagrid->buildPager();

        $pager = $datagrid->getPager();
        $results = $pager->getResults();

        /**
         * @var MediaInterface $media
         */
        $items = [];
        foreach($results as $media) {
            $items[] = [
                'id' => $media->getId(),
                'label' => $this->templating->render('@MediaMonksSonataMedia/CRUD/autocomplete.html.twig', [
                    'media' => $media
                ])
            ];
        }

        return new JsonResponse([
            'status' => 'OK',
            'more' => false,
            'items' => $items
        ]);
    }
}

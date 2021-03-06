<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Entities\StoryWZ;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Helper\WorkZone as WorkzoneHelper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZone implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', $this->call('displayWorkzone'));

        $controllers->get('/Browse/', $this->call('browse'));

        $controllers->get('/Browse/Search/', $this->call('browserSearch'));

        $controllers->get('/Browse/Basket/{basket_id}/', $this->call('browseBasket'))
            ->assert('basket_id', '\d+');

        $controllers->post('/attachStories/', $this->call('attachStories'));

        $controllers->post('/detachStory/{sbas_id}/{record_id}/', $this->call('detachStory'))
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }

    public function displayWorkzone(Application $app)
    {
        $params = array(
            'WorkZone'      => new WorkzoneHelper($app['phraseanet.core'], $app['request'])
            , 'selected_type' => $app['request']->get('type')
            , 'selected_id'   => $app['request']->get('id')
            , 'srt'           => $app['request']->get('sort')
        );

        return $app['twig']->render('prod/WorkZone/WorkZone.html.twig', $params);
    }

    public function browse(Application $app)
    {
        return $app['twig']->render('prod/WorkZone/Browser/Browser.html.twig');
    }

    public function browserSearch(Application $app)
    {
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        $request = $app['request'];

        $em = $app['phraseanet.core']->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $BasketRepo = $em->getRepository('\Entities\Basket');

        $Page = (int) $request->get('Page', 0);

        $PerPage = 10;
        $offsetStart = max(($Page - 1) * $PerPage, 0);

        $Baskets = $BasketRepo->findWorkzoneBasket(
            $user
            , $request->get('Query')
            , $request->get('Year')
            , $request->get('Type')
            , $offsetStart
            , $PerPage
        );

        $page = floor($offsetStart / $PerPage) + 1;
        $maxPage = floor(count($Baskets) / $PerPage) + 1;

        $params = array(
            'Baskets' => $Baskets
            , 'Page'    => $page
            , 'MaxPage' => $maxPage
            , 'Total'   => count($Baskets)
            , 'Query'   => $request->get('Query')
            , 'Year'    => $request->get('Year')
            , 'Type'    => $request->get('Type')
        );

        return $app['twig']->render('prod/WorkZone/Browser/Results.html.twig', $params);
    }

    public function browseBasket(Application $app, Request $request, $basket_id)
    {
        $basket = $app['phraseanet.core']
            ->getEntityManager()
            ->getRepository('\Entities\Basket')
            ->findUserBasket($basket_id, $app['phraseanet.core']->getAuthenticatedUser(), false);

        return $app['twig']->render('prod/WorkZone/Browser/Basket.html.twig', array('Basket' => $basket));
    }

    public function attachStories(Application $app, Request $request)
    {
        if ( ! $request->get('stories')) {
            throw new \Exception_BadRequest();
        }

        $user = $app['phraseanet.core']->getAuthenticatedUser();

        $em = $app['phraseanet.core']->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $StoryWZRepo = $em->getRepository('\Entities\StoryWZ');

        $alreadyFixed = $done = 0;

        $stories = $request->get('stories', array());

        foreach ($stories as $element) {
            $element = explode('_', $element);
            $Story = new \record_adapter($element[0], $element[1]);

            if ( ! $Story->is_grouping()) {
                throw new \Exception('You can only attach stories');
            }

            if ( ! $user->ACL()->has_access_to_base($Story->get_base_id())) {
                throw new \Exception_Forbidden('You do not have access to this Story');
            }

            if ($StoryWZRepo->findUserStory($user, $Story)) {
                $alreadyFixed ++;
                continue;
            }

            $StoryWZ = new StoryWZ();
            $StoryWZ->setUser($user);
            $StoryWZ->setRecord($Story);

            $em->persist($StoryWZ);
            $done ++;
        }

        $em->flush();

        if ($alreadyFixed === 0) {
            if ($done <= 1) {
                $message = sprintf(
                    _('%d Story attached to the WorkZone')
                    , $done
                );
            } else {
                $message = sprintf(
                    _('%d Stories attached to the WorkZone')
                    , $done
                );
            }
        } else {
            if ($done <= 1) {
                $message = sprintf(
                    _('%1$d Story attached to the WorkZone, %2$d already attached')
                    , $done
                    , $alreadyFixed
                );
            } else {
                $message = sprintf(
                    _('%1$d Stories attached to the WorkZone, %2$d already attached')
                    , $done
                    , $alreadyFixed
                );
            }
        }

        if ($request->getRequestFormat() == 'json') {
            return $app->json(array(
                    'success' => true
                    , 'message' => $message
                ));
        }

        return $app->redirect('/{sbas_id}/{record_id}/');
    }

    public function detachStory(Application $app, Request $request, $sbas_id, $record_id)
    {
        $Story = new \record_adapter($sbas_id, $record_id);

        $user = $app['phraseanet.core']->getAuthenticatedUser();

        $em = $app['phraseanet.core']->getEntityManager();

        $repository = $em->getRepository('\Entities\StoryWZ');

        /* @var $repository \Repositories\StoryWZRepository */
        $StoryWZ = $repository->findUserStory($user, $Story);

        if ( ! $StoryWZ) {
            throw new \Exception_NotFound('Story not found');
        }

        $em->remove($StoryWZ);
        $em->flush();

        if ($request->getRequestFormat() == 'json') {
            return $app->json(array(
                    'success' => true
                    , 'message' => _('Story detached from the WorkZone')
                ));
        }

        return $app->redirect('/');
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}

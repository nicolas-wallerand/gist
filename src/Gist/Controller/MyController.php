<?php

namespace Gist\Controller;

use Symfony\Component\HttpFoundation\Request;
use Gist\Form\DeleteGistForm;
use Gist\Form\FilterGistForm;
use Gist\Form\UserPasswordForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MyController.
 *
 * @author Simon Vieille <simon@deblan.fr>
 */
class MyController extends Controller
{
    /**
     * "My" page.
     *
     * @param Request $request
     * @param int     $page
     *
     * @return string
     */
    public function myAction(Request $request, $page)
    {
        $page = (int) $page;
        $app = $this->getApp();

        $deleteForm = new DeleteGistForm($app['form.factory'], $app['translator']);
        $deleteForm = $deleteForm->build()->getForm();

        $options = array(
            'type' => 'all',
            'cipher' => 'anyway',
        );

        $filterForm = new FilterGistForm(
            $app['form.factory'],
            $app['translator'],
            $options,
            ['csrf_protection' => false]
        );

        $filterForm = $filterForm->build()->getForm();

        $passwordForm = new UserPasswordForm($app['form.factory'], $app['translator']);
        $passwordForm = $passwordForm->build()->getForm();

        if ($request->query->has('filter')) {
            $filterForm->submit($request);

            if ($filterForm->isValid()) {
                $options = $filterForm->getData();
            }
        }

        $gists = $this->getUser()->getGistsPager($page, $options);

        if ($request->isMethod('post')) {
            $deleteForm->handleRequest($request);
            $passwordForm->handleRequest($request);

            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                $id = (int) $deleteForm->getData()['id'];

                foreach ($gists as $gist) {
                    if ($gist->getId() === $id) {
                        $gist->delete();
                        $deleted = true;
                        $gists = $this->getUser()->getGistsPager($page, $options);
                    }
                }
            }

            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
                $currentPassword = $passwordForm->getData()['currentPassword'];
                $newPassword = $passwordForm->getData()['newPassword'];
                $passwordUpdated = 0;

                if ($app['user.provider']->isCurrentUserPassword($this->getUser(), $currentPassword)) {
                    $app['user.provider']->updateUserPassword(
                        $this->getUser(),
                        $newPassword
                    );

                    $passwordUpdated = 1;
                }

                return new RedirectResponse(
                    $app['url_generator']->generate(
                        'my',
                        [
                            'passwordUpdated' => $passwordUpdated,
                        ]
                    )
                );
            }
        }

        return $this->render(
            'My/my.html.twig',
            array(
                'gists' => $gists,
                'page' => $page,
                'deleteForm' => $deleteForm->createView(),
                'filterForm' => $filterForm->createView(),
                'passwordForm' => $passwordForm->createView(),
                'deleted' => !empty($deleted),
            )
        );
    }
}

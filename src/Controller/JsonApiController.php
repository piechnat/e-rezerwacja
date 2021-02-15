<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jsonapi")
 */
class JsonApiController extends AbstractController
{
    /**
     * @Route("/find/user", name="jsonapi_find_user")
     */
    public function findUser(Request $request, UserRepository $userRepo): Response
    {
        $matches = $userRepo->match($request->get('term', ''));
        $result = [];
        foreach ($matches as $val) {
            $result[] = ['id' => $val['email'], 'text' => $val['fullname']];
        }

        return $this->json(['results' => $result]);
    }

    /**
     * @Route("/find/users", name="jsonapi_find_users")
     */
    public function findUsers(Request $request, UserRepository $userRepo): Response
    {
        $matches = $userRepo->match($request->get('term', ''));
        $result = [];
        foreach ($matches as $val) {
            $username = strstr($val['email'], '@', true);
            $result[] = ['id' => $val['id'], 'text' => "{$val['fullname']} ({$username})"];
        }

        return $this->json(['results' => $result]);
    }

    /**
     * @Route("/find/room", name="jsonapi_find_room")
     */
    public function findRoom(Request $request, RoomRepository $roomRepo): Response
    {
        $matches = $roomRepo->match($request->get('term', ''));
        $result = [];
        foreach ($matches as $val) {
            $result[] = ['id' => $val['title'], 'text' => $val['title']];
        }

        return $this->json(['results' => $result]);
    }
}

<?php

namespace App\Service;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as Cache;

class RedditHandler extends AbstractController
{
    private string $auth_uri = 'https://www.reddit.com/api/v1/access_token';
    private string $base_url = 'https://api.reddit.com';
    private Client $auth;
    private Client $client;
    private SessionInterface $session;

    public function __construct (SessionInterface $session)
    {
        $this->session = $session;
        $this->client = new Client([
            'base_uri' => 'https://oauth.reddit.com',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->get('reddit-token'),
            ]
        ]);
    }

    public function getBearer (Request $request) : void
    {
        try {
            $client = new Client([
                'timeout' => 2.0,
                'auth' => [
                    $this->getParameter('app.reddit.public'),
                    $this->getParameter('app.reddit.secret'),
                ]
            ]);
            $response = $client->request('POST', $this->auth_uri, [
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => $request->get('username'),
                    'password' => $request->get('password'),
                ]
            ]);

            $body = json_decode($response->getBody());
            $this->session->set('reddit-token', $body->access_token);
        }
        catch (GuzzleException $e) {
            dd($e);
        }
    }

    public function loadSubreddit ()
    {
        $cache = new Cache();
        $cherry_picked = [];
        $award = 'award_2abfe3c4-1d34-466b-98ba-1a756ffceb48';

        try {
            $rawResponse = $this->client->request('GET', 'r/translation_french_fr/new.json?limit=100');
            $response = json_decode($rawResponse->getBody());
            $posts = $response->data->children;

            foreach($posts as $i => $post) {
                $id = $post->data->id;

                if ($cache->hasItem($id)) {
                    $cherry_picked[$id] = unserialize($post);
                }

                $detail = $this->client->request('GET', $post->data->permalink);
                $comments = json_decode($detail->getBody());
                $title = $comments[0]->data->children[0]->data->title;
                $comment_block = $comments[1]->data->children;
                $picked = [];

                foreach ($comment_block as $j => $comment) {
                    if ($j !== 0) {
                        if ($this->filterPost($post)) {
                            continue 2;
                        } else {
                            $picked[] = [
                                'comment' => $comment->data->body,
                                'comment_id' => $comment->data->id,
                            ];
                        }
                    }
                }

                $cherry_picked[$post->data->id] = [
                    'title' => $title,
                    'id' => $id,
                    'comments' => $picked,
                ];

                $cache_post = $cache->getItem($id);
                $cache_post->set(serialize($post));
                $cache_post->tag(['translation_posts']);
                $cache->save($cache_post);
            }

        } catch (GuzzleException $e) {
            if ($e->getCode() == 401) {
                return $this->redirect($this->generateUrl('app_logout'));
            }
        }

        return json_encode($cherry_picked);
    }

    private function filterPost ($post): bool
    {
        $response = FALSE;

        if ($post->data->author === 'Jaeger767') {
            $response = TRUE;
        }

        if (!empty($post->data->all_awardings)) {
            $response = TRUE;
        }

        return $response;
    }

}
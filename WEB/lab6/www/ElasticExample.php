<?php

namespace App;

use App\Helpers\ClientFactory;

class ElasticExample
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://elasticsearch:9200/');
    }

    /**
     * Создание индекса для пользователей
     */
    public function createUsersIndex()
	{
		$mapping = [
			'settings' => [
				'number_of_shards' => 1,
				'number_of_replicas' => 0
			],
			'mappings' => [
				'properties' => [
					'username' => ['type' => 'keyword'],
					'email' => ['type' => 'keyword'],
					'full_name' => ['type' => 'text'],
					'bio' => ['type' => 'text'],
					'city' => ['type' => 'keyword'],
					'age' => ['type' => 'integer'],
					'interests' => ['type' => 'text'],
					'created_at' => ['type' => 'date', 'format' => 'strict_date_optional_time||epoch_millis']
				]
			]
		];

		try {
			$response = $this->client->put('users', [
				'json' => $mapping
			]);
		} catch (\Exception $e) {
			// Если индекс уже существует, просто продолжаем
			if (strpos($e->getMessage(), 'resource_already_exists_exception') === false) {
				throw $e;
			}
			return ['acknowledged' => true];
		}
		
		return json_decode($response->getBody()->getContents(), true);
	}

    /**
     * Создание индекса для постов
     */
    public function createPostsIndex()
	{
		$mapping = [
			'settings' => [
				'number_of_shards' => 1,
				'number_of_replicas' => 0
			],
			'mappings' => [
				'properties' => [
					'user_id' => ['type' => 'keyword'], // Изменено с integer на keyword
					'username' => ['type' => 'keyword'],
					'title' => ['type' => 'text'],
					'content' => ['type' => 'text'],
					'hashtags' => ['type' => 'keyword'],
					'likes_count' => ['type' => 'integer'],
					'comments_count' => ['type' => 'integer'],
					'created_at' => ['type' => 'date', 'format' => 'strict_date_optional_time||epoch_millis'],
					'is_public' => ['type' => 'boolean']
				]
			]
		];

		try {
			$response = $this->client->put('posts', [
				'json' => $mapping
			]);
		} catch (\Exception $e) {
			// Если индекс уже существует, просто продолжаем
			if (strpos($e->getMessage(), 'resource_already_exists_exception') === false) {
				throw $e;
			}
			return ['acknowledged' => true];
		}
		
		return json_decode($response->getBody()->getContents(), true);
	}
	
	public function getClient()
	{
		return $this->client;
	}
	
    /**
     * Добавление пользователя
     */
    public function addUser($userData)
    {
        $response = $this->client->post('users/_doc', [
            'json' => $userData
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Добавление поста
     */
    public function addPost($postData)
    {
        $response = $this->client->post('posts/_doc', [
            'json' => $postData
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Поиск пользователей по интересам
     */
    public function searchUsersByInterests($interests, $city = null)
    {
        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['match' => ['interests' => $interests]]
                    ]
                ]
            ]
        ];

        if ($city) {
            $query['query']['bool']['filter'] = [
                ['term' => ['city' => $city]]
            ];
        }

        $response = $this->client->get('users/_search', [
            'json' => $query
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Поиск постов по тексту
     */
    public function searchPosts($searchText, $hashtag = null)
    {
        $query = [
            'query' => [
                'bool' => [
                    'should' => [
                        ['match' => ['title' => $searchText]],
                        ['match' => ['content' => $searchText]]
                    ]
                ]
            ],
            'sort' => [
                ['created_at' => ['order' => 'desc']],
                ['likes_count' => ['order' => 'desc']]
            ]
        ];

        if ($hashtag) {
            $query['query']['bool']['filter'] = [
                ['term' => ['hashtags' => $hashtag]]
            ];
        }

        $response = $this->client->get('posts/_search', [
            'json' => $query
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Статистика по хештегам
     */
    public function getHashtagStats()
    {
        $aggregation = [
            'size' => 0,
            'aggs' => [
                'popular_hashtags' => [
                    'terms' => [
                        'field' => 'hashtags',
                        'size' => 10
                    ]
                ]
            ]
        ];

        $response = $this->client->get('posts/_search', [
            'json' => $aggregation
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Получение рекомендаций постов для пользователя
     */
    public function getPostRecommendations($userId, $userInterests)
    {
        $query = [
            'query' => [
                'bool' => [
                    'must_not' => [
                        ['term' => ['user_id' => $userId]]
                    ],
                    'should' => [
                        ['match' => ['content' => $userInterests]],
                        ['match' => ['hashtags' => $userInterests]]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'sort' => [
                ['created_at' => ['order' => 'desc']],
                ['likes_count' => ['order' => 'desc']]
            ],
            'size' => 10
        ];

        $response = $this->client->get('posts/_search', [
            'json' => $query
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }
}
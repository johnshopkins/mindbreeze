<?php

namespace Mindbreeze;

class Request
{
  protected $http;

  /**
   * API endpoint
   * @var string
   */
  public $url;

  /**
   * Array of properties to fetch from Mindbreeze
   * @var array
   */
  public $properties = [];

  /**
   * Query term
   * @var string
   */
  public $query;

  /**
   * Page from which to return results
   * @var integer
   */
  public $page = 1;

  /**
   * Number of documents to retrieve per page
   * @var integer
   */
  public $perPage = 10;

  /**
   * Number of pages to retrieve in `result_pages`
   * @var integer
   */
  public $pageCount = 10;

  /**
   * Max number of alternative queries to return
   * @var integer
   */
  public $alternatives = 10;

  /**
   * Length of content snippet
   * @var integer
   */
  public $contentSampleLength = 300;

  /**
   * Array of all datasources in the index
   * Ex: Web:GazetteArchives
   * @var array
   */
  public $datasources = [];

  /**
   * Array of constraints based on datasources.
   * For example, 'gazette' => ['Web:GazetteArchivesPages', ['Web:GazetteArchivesWP']]
   * creates a gazette constraint that limits search to the two defined datasources
   * @var array
   */
  public $constraints = [];

  /**
   * Compiled data
   * @var array
   */
  protected $data = [];

  public function __construct($http)
  {
    $this->http = $http;
  }

  /**
   * Send the request to Mindbreeze
   * @return object Mindbreeze\Response
   */
  public function send()
  {
    $response = $this->http->post($this->url, [
      'body' => json_encode($this->compileData()),
      'headers' => ['Content-Type' => 'application/json']
    ]);

    return new \Mindbreeze\Response($response);
  }

  public function setQuery($query)
  {
    $this->query = $query;
    return $this;
  }

  public function setPage($page)
  {
    $this->page = $page;
    return $this;
  }

  /**
   * Add a datasource constraint
   * @param string $constraint A defined constraint
   */
  public function addDatasourceConstraint($constraint)
  {
    if (!isset($this->constraints[$constraint])) {
      return [];
    }

    $in = $this->constraints[$constraint];
    $out = array_diff($this->datasources, $in);

    $this->data['source_context']['constraints'] = [
      'filter_base' => array_map(function ($datasource) {
        return $this->createFilter('fqcategory', $datasource);
      }, array_values($in)),
      'filtered' => array_map(function ($datasource) {
        return $this->createFilter('fqcategory', $datasource);
      }, array_values($out))
    ];
  }

  /**
   * Create boolean filter
   * @param  string $label Filter label
   * @param  string $term  Value of filter
   * @return array  Filter array
   */
  protected function createFilter($label, $term, $type = 'and')
  {
    return [
      $type => [
        [
          'label' => $label,
          'quoted_term' => $term
        ]
      ]
    ];
  }

  /**
   * Compile data to send to Mindbreeze
   * @return array Data
   */
  public function compileData()
  {
    // default data

    $defaults = [
      'content_sample_length' => $this->contentSampleLength,
      'query' => ['unparsed' => $this->query],
      'count' => $this->perPage,
      'max_page_count' => $this->pageCount,
      'alternatives_query_spelling_max_estimated_count' => $this->alternatives,
      'properties' => array_map(function ($property) {
        return [
          'formats' => ['HTML', 'VALUE'],
          'name' => $property
        ];
      }, $this->properties)
    ];

    $this->data = array_merge($defaults, $this->data);

    // pagination

    if ($this->page > 1) {
      $this->data['result_pages'] = [
        'qeng_ids' => $this->getQeng(),
        'pages' => [
          'starts' => [($this->page - 1) * $this->perPage],
          'counts' => [$this->perPage],
          'current_page' => true,
          'page_number' => $this->page
        ]
      ];
    }

    return $this->data;
  }

  /**
   * Retrieve Qeng variables from previous page to use in
   * paginated request to Mindbreeze. Set in Mindbreeze\Response.
   * @return mixed Array (if qeng variables set); otherwise FALSE
   */
  protected function getQeng()
  {
    if (!isset($_SESSION['search_qeng']) || !$_SESSION['search_qeng']) {
      throw new \Exception('On page 2+ of search and QENG variables not set.');
    }

    return $_SESSION['search_qeng'];
  }
}
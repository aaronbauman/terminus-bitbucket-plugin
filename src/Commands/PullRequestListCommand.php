<?php

namespace Pantheon\TerminusBitbucket\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class PullRequestListCommand
 *
 * @package Pantheon\TerminusBitbucket\Commands
 */
class PullRequestListCommand extends BitbucketBase {

  /**
   * List pull requests.
   *
   * @authorize
   *
   * @command bitbucket:pull-request:list
   *
   * @aliases bb:pr:list bitbucket:pr:list pr-list
   *
   * @field-labels
   *     id: ID
   *     title: Title
   *     description: Description
   *     url: URL
   *     destination: Destination Branch
   *     source: Source Branch
   *     state: State
   *     updated_on: Updated Date
   *     created_on: Created Date
   *     author: Author
   *     merge_commit: Merge Commit
   *     closed_by: Closed By
   * @default-fields id,title,source,destination,state,updated_on,author
   * @return RowsOfFields
   *
   * @option string $site The site whose pull requests to list. If not given,
   *   try to guess the site from build metadata.
   *
   * @option int $id Return info for only the given PR number.
   *
   * @option string $state [open|closed|merged|declined|superseded|all] Return
   *   PRs of only the given state. Ignored if $id is given.
   *
   * @usage <site> Lists open pull requests for <site>.
   *
   */
  public function prList(array $options = [
    'site' => NULL,
    'id' => NULL,
    'state' => 'open',
  ]) {
    if (!$this->init($options['site'])) {
      return;
    }

    $stateParameters = [
      'open' => ['OPEN'],
      'closed' => ['MERGED', 'DECLINED', 'SUPERSEDED'],
      'all' => ['MERGED', 'DECLINED', 'SUPERSEDED', 'OPEN'],
      'declined' => ['DECLINED'],
      'superseded' => ['SUPERSEDED'],
      'merged' => ['MERGED'],
    ];
    if (!isset($stateParameters[$options['state']])) {
      throw new TerminusException("branchesForPullRequests - state must be one of: open, closed, all");
    }

    if (empty($options['id'])) {
      $this->log()
        ->notice("Fetching {state} PRs for {site}", [
          'state' => $options['state'],
          'site' => $this->project,
        ]);
      $args = ['state' => implode('&state=', $stateParameters[$options['state']])];
      $data = $this->git_provider->api()
        ->pagedRequest("repositories/{$this->project}/pullrequests/", NULL, $args);
    }
    else {
      $this->log()
        ->notice("Fetching info for PR {id}", ['id' => $options['id']]);
      $data = [
        $this->git_provider->api()
          ->request("repositories/{$this->project}/pullrequests/{$options['id']}"),
      ];
    }
    return $this->rowsOfFieldsFromPullRequestData($data);
  }

  protected function rowsOfFieldsFromPullRequestData($data) {
    $dataFix = [];
    foreach ($data as $pr) {
      $item = [
        'id' => $pr['id'],
        'title' => $pr['title'],
        'description' => $pr['description'],
        'url' => $pr['links']['html']['href'],
        'destination' => $pr['destination']['branch']['name'],
        'source' => $pr['source']['branch']['name'],
        'state' => $pr['state'],
        'updated_on' => $this->config->formatDatetime(strtotime($pr['updated_on'])),
        'created_on' => $this->config->formatDatetime(strtotime($pr['created_on'])),
        'author' => $pr['author']['nickname'],
        'merge_commit' => $pr['merge_commit'],
        'closed_by' => $pr['closed_by'],
      ];
      $dataFix[] = $item;
    }
    return new RowsOfFields($dataFix);
  }

}

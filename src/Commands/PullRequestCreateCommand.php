<?php

namespace Pantheon\TerminusBitbucket\Commands;

/**
 * Class PullRequestCreateCommand
 *
 * @package Pantheon\TerminusBitbucket\Commands
 */
class PullRequestCreateCommand extends BitbucketBase {

  /**
   * Create a pull requests.
   *
   * @authorize
   *
   * @command bitbucket:pull-request:create
   *
   * @aliases bb:pr:create bitbucket:pr:create pr-create
   *
   * @option string $site The site whose pull request to create. If not given,
   *   try to guess the site from build metadata.
   *
   * @option string $source The source branch from which to create the PR. If
   *   not given, defaults to currently checked out branch.
   *
   * @option string $target The target branch into which the PR will be merged.
   *   If not give, defaults to repository.mainbranch.
   *
   * @option string $title Short title for the pull request.
   *
   * @option string $description Extended description of the pull request.
   *   Defaults to null.
   *
   * @options string $reviewers Comma-separated list of UUIDs of reviewers to
   *   be assigned. Defaults to null.
   *
   * @options bool $close Whether to close the source branch upon merging.
   *   Defaults to FALSE.
   *
   * @usage Creates a pull request.
   *
   */
  public function prCreate($options = [
    'site' => NULL,
    'source' => NULL,
    'target' => NULL,
    'title' => NULL,
    'description' => NULL,
    'reviewers' => [],
    'close' => FALSE,
  ]) {
    if (!$this->init($options['site'])) {
      return;
    }
    unset($options['site']);
    if (empty($options['title'])) {
      $options['title'] = 'Pull request from Terminus.';
    }
    if (empty($options['source'])) {
      $options['source'] = $this->buildMetadata['ref'];
    }
    $options['source'] = ['branch' => ['name' => $options['source']]];

    if (!empty($options['target'])) {
      $options['destination'] = ['branch' => ['name' => $options['target']]];
      unset($options['target']);
    }

    if (!empty($options[reviewers'])) {
      $rev = $options['reviewers'];
      $options['reviewers'] = [];
      foreach ($rev as $reviewer) {
        $options['reviewers'][] = ['uuid' => $reviewer];
      }
    }

    $options['close_source_branch'] = $options['close'] ? TRUE : FALSE;
    unset($options['close']);

    $options = array_filter($options);

    $this->log()
      ->notice("Creating PR {title} from {source} to {target}", [$options]);
    if ($data =
      $this->git_provider->api()
        ->request("repositories/{$this->project}/pullrequests", $options, 'POST')) {
      $this->log()
        ->notice("Pull request created successfully {url}", ["url" => $data['links']['html']['href']]);
    }
  }

}

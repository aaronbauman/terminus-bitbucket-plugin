<?php

namespace Pantheon\TerminusBitbucket\Commands;

/**
 * Class PullRequestCloseCommand
 *
 * @package Pantheon\TerminusBitbucket\Commands
 */
class PullRequestCloseCommand extends BitbucketBase {

  /**
   * Close a pull request.
   *
   * @authorize
   *
   * @command bitbucket:pull-request:close
   *
   * @aliases bb:pr:close bitbucket:pr:close pr-close
   *
   * @param int $id The ID of the PR to be closed.
   *
   * @option string $site The site whose pull request to close. If not given,
   *   try to guess the site from build metadata.
   *
   * @usage Close a  pull request.
   *
   */
  public function prClose($id, $options = ['site' => NULL]) {
    if (!$this->init($options['site'])) {
      return;
    }

    if (!$this->confirm('Are you sure you want to close PR {id} for {project}?', [
      'id' => $id,
      'project' => $this->project,
    ])) {
      return;
    }

    $this->log()
      ->notice("Closing PR {id} on {project}", [
        'id' => $id,
        'project' => $this->project,
      ]);
    if ($data =
      $this->git_provider->api()
        ->request("repositories/{$this->project}/pullrequests/$id/decline", [], 'POST')) {
      $this->log()->notice("Pull request {id} has been closed.", ["id" => $id]);
    }
  }

}

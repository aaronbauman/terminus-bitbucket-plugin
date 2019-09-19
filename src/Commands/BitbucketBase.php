<?php

namespace Pantheon\TerminusBitbucket\Commands;

use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\TerminusBuildTools\Commands\BuildToolsBase;
use Pantheon\TerminusBuildTools\ServiceProviders\RepositoryProviders\Bitbucket\BitbucketProvider;

abstract class BitbucketBase extends BuildToolsBase
{
  /**
   * Bitbucket git provider.
   *
   * @var \Pantheon\TerminusBuildTools\ServiceProviders\RepositoryProviders\Bitbucket\BitbucketProvider
   */
  protected $git_provider;

  /**
   * Name of bitbucket project for the current site.
   *
   * @var string
   */
  protected $project;

  /**
   * Build metadata.
   *
   * @var array
   */
  protected $buildMetadata;

  /**
   * Path to repository directory.
   *
   * @var string
   */
  protected $repositoryDir;

  private function isBitbucket($site_name) {
    // Try to find the site name from build metadata if not given.
    if (empty($site_name)) {
      $this->repositoryDir = getcwd();
      $buildMetadata = $this->getBuildMetadata($this->repositoryDir);
      // Set metadata site so that getMetadataUrl() doesn't complain.
      $buildMetadata['site'] = $this->repositoryDir;
    }
    else {
      // Look up the oldest environments matching the delete pattern
      $buildMetadata = $this->retrieveBuildMetadata("{$site_name}.dev");
    }
    $this->buildMetadata = $buildMetadata;
    $url = $this->getMetadataUrl($buildMetadata);
    $this->project = $this->projectFromRemoteUrl($url);

    // Create a git repository service provider appropriate to the URL
    return $this->inferGitProviderFromUrl($url) instanceof BitbucketProvider;
  }

  protected function init($site) {
    $this->log()->notice("Initializing bitbucket client");

    // Initializes project and buildMetadata properties.
    if (!$this->isBitbucket($site)) {
      $this->log()->error('Site "{site}" does not use Bitbucket.', ['{site}' => $site]);
      return FALSE;
    }
    // Initializes $this->git_provider.
    $this->createGitProvider('bitbucket');

    // Ensure that credentials for the Git provider are available
    $this->providerManager()->validateCredentials();

    return $this->git_provider instanceof BitbucketProvider;
  }

}
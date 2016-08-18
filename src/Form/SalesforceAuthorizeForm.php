<?php

/**
 * @file
 * Contains \Drupal\salesforce\Form\SalesforceAuthorizeForm.
 */

namespace Drupal\salesforce\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salesforce\SalesforceClient;
use Drupal\salesforce\Exception;
use Drupal\salesforce\RestClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates authorization form for Salesforce.
 */
class SalesforceAuthorizeForm extends ConfigFormBase {

  protected $sf_client;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context to use.
   * @param \Drupal\salesforce\SalesforceClient $sf_client
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactory $config_factory, RestClient $salesforce_client) {
    parent::__construct($config_factory);
    $this->sf_client = $salesforce_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('salesforce.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'salesforce_oauth';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'salesforce.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('salesforce.settings');

    $form['message'] = array(
      '#type' => 'item',
      '#markup' => $this->t('Authorize this website to communicate with Salesforce by entering the consumer key and secret from a remote application. Clicking authorize will redirect you to Salesforce where you will be asked to grant access.'),
    );

    $form['consumer_key'] = array(
      '#title' => $this->t('Salesforce consumer key'),
      '#type' => 'textfield',
      '#description' => $this->t('Consumer key of the Salesforce remote application you want to grant access to'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_key'),
    );
    $form['consumer_secret'] = array(
      '#title' => $this->t('Salesforce consumer secret'),
      '#type' => 'textfield',
      '#description' => $this->t('Consumer secret of the Salesforce remote application you want to grant access to'),
      '#required' => TRUE,
      '#default_value' => $config->get('consumer_secret'),
    );
    $form['login_url'] = array(
      '#title' => $this->t('Login URL'),
      '#type' => 'textfield',
      '#default_value' => ((is_null($config->get('login_url'))) ? 'https://login.salesforce.com' : $config->get('login_url')),
    );

    // If we're authenticated, show a list of available REST resources.
    if ($config->get('consumer_key') && $config->get('consumer_secret')) {
      // If fully configured, attempt to connect to Salesforce and return a list
      // of resources.
      if ($this->sf_client->isAuthorized()) {
        try {
          $resources = $this->sf_client->listResources();
          foreach ($resources as $key => $path) {
            $items[] = $key . ': ' . $path;
          }
          $form['resources'] = array(
            '#title' => $this->t('Your Salesforce instance is authorized and has access to the following resources:'),
            '#items' => $items,
            '#theme' => 'item_list'
          );
        }
        catch(\GuzzleHttp\Exception\ClientException $e) {
          salesforce_set_message($e->getMessage(), 'warning');
        }
      }
      else {
        salesforce_set_message(t('Salesforce needs to be authorized to connect to this website.'), 'error');
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('salesforce.settings')
      ->set('consumer_key', $values['consumer_key'])
      ->set('consumer_secret', $values['consumer_secret'])
      ->set('login_url', $values['login_url'])
      ->save();

    try {
      return $this->sf_client->getAuthorizationCode();
    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      // Set form error
    }

    parent::submitForm($form, $form_state);
  }

}

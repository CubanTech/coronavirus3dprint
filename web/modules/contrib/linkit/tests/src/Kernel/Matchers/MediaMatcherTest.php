<?php

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests media matcher.
 *
 * @group linkit
 */
class MediaMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file_test', 'file', 'media', 'image', 'field'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('system', ['key_value_expire']);
    $this->installSchema('file', ['file_usage']);

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    // Set up media bundle and fields.
    $media_type = MediaType::create([
      'label' => 'test',
      'id' => 'test',
      'description' => 'Test type.',
      'source' => 'file',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();

    // Linkit doesn't care about the actual resource, only the entity.
    foreach (['gif', 'jpg', 'png'] as $ext) {
      $file = File::create([
        'uid' => 1,
        'filename' => 'image-test.' . $ext,
        'uri' => 'public://image-test.' . $ext,
        'filemime' => 'text/plain',
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->save();

      $media = Media::create([
        'bundle' => 'test',
        $source_field->getName() => ['target_id' => $file->id()],
      ]);
      $media->save();
    }

    // Create user 1 who has special permissions.
    \Drupal::currentUser()->setAccount($this->createUser(['uid' => 1]));
  }

  /**
   * Tests media matcher.
   */
  public function testMediaMatcherWithDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:media', []);
    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions.');
  }

}

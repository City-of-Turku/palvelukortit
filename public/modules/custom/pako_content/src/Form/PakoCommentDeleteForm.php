<?php

namespace Drupal\pako_content\Form;

use Drupal\comment\Form\DeleteForm;

/**
 * Base handler for comment forms.
 *
 * @internal
 */
class PakoCommentDeleteForm extends DeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The amendment proposal have been deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('comment')->notice('Deleted amendment proposal @cid .', ['@cid' => $this->entity->id()]);
  }

}

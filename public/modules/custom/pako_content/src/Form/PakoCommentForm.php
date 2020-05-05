<?php

namespace Drupal\pako_content\Form;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\CommentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Base handler for comment forms.
 *
 * @internal
 */
class PakoCommentForm extends CommentForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $comment = $this->entity;
    $entity = $comment->getCommentedEntity();
    $field_name = $comment->getFieldName();
    $uri = $entity->toUrl();
    $logger = $this->logger('comment');

    if ($this->currentUser->hasPermission('post comments') && ($this->currentUser->hasPermission('administer comments') || $entity->{$field_name}->status == CommentItemInterface::OPEN)) {
      $comment->save();
      $form_state->setValue('cid', $comment->id());

      // Add a log entry.
      $logger->notice('Comment posted: %subject.', [
        '%subject' => $comment->getSubject(),
        'link' => Link::fromTextAndUrl(t('View'), $comment->toUrl()->setOption('fragment', 'comment-' . $comment->id()))->toString(),
      ]);

      $this->messenger()->addStatus($this->t('Your amendment proposal have been sent.'));

      $query = [];
      // Find the current display page for this comment.
      $field_definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$field_name];
      $page = $this->entityTypeManager->getStorage('comment')->getDisplayOrdinal($comment, $field_definition->getSetting('default_mode'), $field_definition->getSetting('per_page'));
      if ($page > 0) {
        $query['page'] = $page;
      }
      // Redirect to the newly posted comment.
      $uri->setOption('query', $query);
      $uri->setOption('fragment', 'comment-' . $comment->id());
    }
    else {
      $logger->warning('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', ['%subject' => $comment->getSubject()]);
      $this->messenger()->addError($this->t('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', ['%subject' => $comment->getSubject()]));
      // Redirect the user to the entity they are commenting on.
    }
    $form_state->setRedirectUrl($uri);
  }

}

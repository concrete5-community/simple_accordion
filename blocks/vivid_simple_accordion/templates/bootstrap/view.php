<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var int $bID
 * @var array $items
 * @var bool $editMode (only if $items is empty)
 * @var string $openTag
 * @var string $closeTag
 */

if ($items === []) {
    if ($editMode) {
        ?>
        <div class="well"><?= t('You did not add any items to the accordion.') ?></div>
        <?php
    }
    return;
}

?>
<div class="panel-group" id="vivid-simple-accordion-<?= $bID ?>" role="tablist" aria-multiselectable="true">
    <?php
    foreach ($items as $index => $item) {
        if ($item['state'] == 'open'){
            $state = ' in';
        }
        else {
            $state = '';
        }
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading<?= $bID ?>-<?= $index ?>">
            <?= $openTag ?>
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?= $bID ?>-<?= $index ?>" aria-expanded="true" aria-controls="collapse<?= $bID ?>-<?= $index ?>">
                    <?= $item['title'] ?>
                </a>
                <?= $closeTag ?>
            </div>
            <div id="collapse<?= $bID ?>-<?= $index ?>" class="panel-collapse collapse<?=$state?>" role="tabpanel" aria-labelledby="heading<?= $bID ?>-<?= $index ?>">
                <div class="panel-body">
                    <?= $item['description'] ?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>

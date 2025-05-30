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
<div class="vivid-simple-accordion" id="vivid-simple-accordion-<?= $bID ?>">
    <?php
    foreach ($items as $item) {
        ?>
        <div class="simple-accordion-group <?= $item['state'] ?>">
            <div class="simple-accordion-title-shell">
                <?= $openTag ?><?= $item['title'] ?><?= $closeTag ?>
            </div>
            <div class="simple-accordion-description">
                <?= $item['description'] ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>

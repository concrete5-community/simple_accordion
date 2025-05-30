<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Block\View\BlockView $view
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Application\Service\UserInterface $ui
 * @var Concrete\Core\Editor\CkeditorEditor $editor
 *
 * @var string $framework
 * @var string $semantic
 * @var array $items
 */

$tabsPrefix = version_compare(APP_VERSION, '9') < 0 ? 'ccm-tab-content-' : '';

?>

<?= $ui->tabs([
        ['vsa-pane-items', t('Items'), true],
        ['vsa-pane-settings', t('Settings')],
]) ?>
<div class="tab-content">
    <div class="ccm-tab-content tab-pane active" role="tabpanel" id="<?= $tabsPrefix ?>vsa-pane-items">
        <div class="items-container"></div>
        <div class="small text-muted text-right text-end">
            <?= t('You can rearrange items if needed.') ?>
        </div>
        <div style="padding-top: 5px">
            <button type="button" class="btn btn-success btn-add-item"><?= t('Add Item') ?></button>
        </div>
    </div>
    <div class="ccm-tab-content tab-pane" role="tabpanel" id="<?= $tabsPrefix ?>vsa-pane-settings">
        <div class="form-group">
            <label class="form-label"><?= t('Framework') ?></label>
            <?= $form->select(
                'framework',
                [
                    '' => t('None'),
                    'bootstrap' => 'Bootstrap 3',
                ],
                $framework
            ) ?>
            <div class="small text-muted">
                <?= t('If your theme uses the bootstrap framework, then select that. Otherwise, just choose none') ?>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= t('Semantic Tag for Title') ?></label>
            <?= $form->select(
                'semantic',
                [
                    'h2' => t('H2'),
                    'h3' => t('H3'),
                    'h4 '=> t('H4'),
                    'span' => t('Span'),
                    'paragraph' => t('Paragraph'),
                ],
                $semantic
            ) ?>
        </div>
    </div>
</div>

<script type="text/template" id="vsa-item-template">
    <div class="item panel panel-default">
        <div class="panel-heading" style="cursor: move">
            <?php
            if (version_compare(APP_VERSION, '9') < 0) {
                ?>
                <div class="row">
                    <div class="col-xs-2 label-shell" style="margin-top: 5px">
                        <div style="text-align: right">
                            <span style="float: left; margin-top: 3px">
                                &#x2195; <!-- UP DOWN ARROW -->
                            </style>
                            <?= t('Title') ?>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <input type="text" class="form-control" name="title[]" value="<%= title %>">
                    </div>
                    <div class="col-xs-4 text-right">
                        <button type="button" class="btn btn-default btn-edit-item"><?= t('Edit') ?></button>
                        <button type="button" class="btn btn-danger btn-delete-item"><?= t('Delete') ?></button>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="input-group mt-2">
                    <span class="input-group-text">
                        &#x2195; <!-- UP DOWN ARROW -->
                        <?= t('Title') ?>
                    </span>
                    <input type="text" class="form-control" name="title[]" value="<%= title %>">
                    <button type="button" class="btn btn-primary btn-edit-item"><?= t('Edit') ?></button>
                    <button type="button" class="btn btn-danger btn-delete-item"><?= t('Delete') ?></button>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="panel-body form-horizontal" style="display: none">
            <div class="form-group">
                <label class="col-xs-3 control-label" for="vsa-description<%= index %>"><?= t('Description') ?>:</label>
                <div class="col-xs-9">
                    <textarea name="description[]" id="vsa-description<%= index %>"><%= description %></textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label"><?= t('State') ?></label>
                <div class="col-xs-9">
                    <select class="form-control" name="state[]">
                        <option value="closed" <%= state == 'closed' ? 'selected' : '' %>><?= t('Closed') ?></option>
                        <option value="open" <%= state=='open' ? 'selected' : '' %>><?= t('Open') ?></option>
                    </select>
                </div>
            </div>
        </div>
        <input type="hidden" name="sortOrder[]" value="<%= index %>" />
    </div>
</script>

<script>
(function() {

var $form = $('#ccm-block-form');
var $itemsContainer = $form.find('.items-container');
var itemTemplate = _.template($form.find('#vsa-item-template').html());
var initEditor = <?= $editor->getEditorInitJSFunction() ?>;

var numCreatedItems = 0;

function createItem(data, focalize)
{
    data = $.extend({}, data || {}, {index: numCreatedItems++});
    $itemsContainer.append(itemTemplate(data));
    initEditor('#vsa-description' + data.index);
    if (focalize) {
        var newItem = $itemsContainer.find('.item').last();
        var thisModal = $form.closest('.ui-dialog-content');
        thisModal.scrollTop(newItem.offset().top);
    }
}

$form.find('.btn-add-item').on('click', function() {
    createItem(
        {
            title: '',
            description: '',
            state: '',
        },
        true
    );
});

$form.on('click', '.btn-edit-item', function() {
    $(this).closest('.item').find('.panel-body').toggle();
});

$form.on('click', '.btn-delete-item', function() {
    if (!window.confirm(<?= json_encode(t('Are you sure?')) ?>)) {
        return;
    }
    $(this).closest('.item').remove();
});


$('.items-container').sortable({
    handle: '.panel-heading',
    axis: 'y',
});


<?php
foreach ($items as $item) {
    ?>
    createItem(<?= json_encode($item) ?>);
    <?php
}
?>

})();
</script>

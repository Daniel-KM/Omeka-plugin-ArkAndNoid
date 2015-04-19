<?php
// Html policy.
if ($html) :
    $title = __('Ark Policy Statement');
    echo head(array(
        'title' => $title,
    ));
?>
<div id="primary">
    <?php echo flash(); ?>
    <h1><?php echo $title; ?></h1>
    <?php echo $policy; ?>
</div>
<?php echo foot(); ?>
<?php

// Unformatted policy.
else:
    echo $policy;
endif; ?>

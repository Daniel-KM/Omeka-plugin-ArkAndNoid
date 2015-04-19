<?php // This shoud returns a brief machine- and eye-readable metadata record (not the full Dublin Core).
$title = metadata($record, array('Dublin Core', 'Title')) ?: __('[No Title]');
$titleParts[] = strip_formatting($title);
$titleParts[] = option('site_title');

$title = metadata($record, array('Dublin Core', 'Title'), array('no_escape' => true));
$creators = metadata($record, array('Dublin Core', 'Creator'), array('no_escape' => true, 'all' => true));
$publisher = metadata($record, array('Dublin Core', 'Publisher'), array('no_escape' => true));
$date = metadata($record, array('Dublin Core', 'Date'), array('no_escape' => true));
$itemType = get_class($record) == 'Item' ? metadata($record, 'item_type_name') : '';
$description = metadata($record, array('Dublin Core', 'Description'), array('no_escape' => true, 'snippet' => 140));
// TODO Cut at 80 characters and add fixed spaces at the start of each new line.
function _oneCleanLine($string) {
    return trim(preg_replace('/\s\s+/', ' ', strip_formatting($string)));
}
?>
erc:
<?php foreach ($creators as $creator): ?>
who:    <?php echo _oneCleanLine($creator) . PHP_EOL; ?>
<?php endforeach; ?>
<?php if ($publisher): ?>
who:    <?php echo _oneCleanLine($publisher) . PHP_EOL; ?>
<?php endif; ?>
<?php if (empty($creators) && empty($publisher)): ?>
who:    <?php echo '(:unkn) anonymous' . PHP_EOL; ?>
<?php endif; ?>
what:   <?php echo ($title ?: '(:unas) value unassigned') . PHP_EOL; ?>
when:   <?php echo ($date ?: '(:unav) value unavailable, possibly unknown') . PHP_EOL; ?>
where:  <?php echo $absolute_ark . PHP_EOL; ?>
<?php if ($itemType): ?>
how:    <?php echo $itemType . PHP_EOL; ?>
<?php endif; ?>
<?php if ($description): ?>
about-how:    <?php echo _oneCleanLine($description) . PHP_EOL; ?>
<?php endif; ?>
<?php if ($note = get_option('ark_note')): ?>
note:   <?php echo _oneCleanLine($note) . PHP_EOL; ?>
<?php endif; ?>
<?php echo $policy; ?>

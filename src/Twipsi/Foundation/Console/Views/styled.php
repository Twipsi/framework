<div class="mx-3 my-1">
    <span class="px-1 bg-<?php echo $bgColor ?> text-<?php echo $fgColor ?> uppercase"><?php echo $title ?></span>
    <span class="<?php if ($title) { echo 'ml-2';} ?>">
        <?php echo htmlspecialchars($content) ?>
    </span>
</div>
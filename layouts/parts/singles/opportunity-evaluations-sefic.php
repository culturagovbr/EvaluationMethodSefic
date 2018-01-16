<div id="evaluations" class="aba-content">
    <?php if($entity->canUser('@control')): // se  ?>
        <?php $this->part('singles/opportunity-evaluations--admin--table-sefic', ['entity' => $entity]) ?>
        <?php $this->part('singles/opportunity-evaluations--admin--buttons', ['entity' => $entity]) ?>
    <?php else: ?>
        <?php $this->part('singles/opportunity-evaluations--committee--table-sefic', ['entity' => $entity]) ?>
    <?php endif; ?>

</div>
<!--#evaluations-->

<?php
// Grade semanal estilo agenda: arraste programas da biblioteca para os dias, mova e estique os blocos.
$programas = q('SELECT p.*, (SELECT COUNT(*) FROM grade g WHERE g.programa_id = p.id) usos FROM programas p ORDER BY nome')->fetchAll();
$grade = q('SELECT id, programa_id, dia, inicio, fim FROM grade ORDER BY dia, inicio')->fetchAll();
$dadosProg = array_map(fn($p) => [
    'id' => (int)$p['id'], 'nome' => $p['nome'], 'apresentador' => $p['apresentador'], 'descricao' => (string)$p['descricao'],
    'cor' => $p['cor'], 'foto' => $p['foto'] ? midia($p['foto'], true) : null, 'usos' => (int)$p['usos'],
], $programas);
$dadosGrade = array_map(fn($g) => array_map('intval', $g), $grade);
admin_inicio('Programação', 'programacao', '<button class="btn btn-fantasma" type="button" id="btnCopiarDia">' . icone('copy') . ' Copiar um dia</button> <a class="btn btn-fantasma" href="' . e(url('/programacao')) . '" target="_blank" rel="noopener">' . icone('eye') . ' Ver no site</a>');
?>
<div class="prog-editor" id="progEditor"
     data-programas="<?= e(json_encode($dadosProg, JSON_UNESCAPED_UNICODE)) ?>"
     data-grade="<?= e(json_encode($dadosGrade)) ?>"
     data-hoje="<?= (int)date('w') ?>" data-minuto="<?= (int)date('G') * 60 + (int)date('i') ?>">
  <aside class="biblioteca">
    <div class="bib-cab">
      <h2>Programas</h2>
      <button class="btn btn-primario btn-peq" type="button" id="btnNovoPrograma"><?= icone('plus') ?> Novo</button>
    </div>
    <p class="dica">Arraste um programa para a grade. Clique para editar.</p>
    <div class="bib-lista" id="bibLista"></div>
  </aside>
  <section class="agenda-caixa">
    <div class="agenda-dicas">
      <span><?= icone('grip') ?> Arraste o bloco para mudar dia/horário</span>
      <span>⇕ Puxe a borda de baixo ou de cima para mudar a duração</span>
      <span><kbd>Ctrl</kbd> + arrastar = copiar</span>
      <span>Encaixe a cada <select id="encaixe"><option value="5">5</option><option value="15" selected>15</option><option value="30">30</option><option value="60">60</option></select> min</span>
    </div>
    <div class="agenda" id="agenda">
      <div class="agenda-cab">
        <div class="agenda-canto"></div>
        <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><div class="agenda-dia-cab<?= $d === (int)date('w') ? ' hoje' : '' ?>" data-dia="<?= $d ?>"><?= DIAS[$d] ?></div><?php endforeach; ?>
      </div>
      <div class="agenda-corpo" id="agendaCorpo">
        <div class="agenda-horas"><?php for ($h = 0; $h < 24; $h++): ?><span><?= sprintf('%02d:00', $h) ?></span><?php endfor; ?></div>
        <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><div class="agenda-col<?= $d === (int)date('w') ? ' hoje' : '' ?>" data-dia="<?= $d ?>"></div><?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<dialog class="modal" id="modalPrograma">
  <form method="dialog" class="modal-corpo" id="formPrograma" enctype="multipart/form-data">
    <h2 id="tituloModalPrograma">Programa</h2>
    <input type="hidden" name="id">
    <label class="campo">Nome<input type="text" name="nome" required maxlength="120"></label>
    <label class="campo">Apresentador(es)<input type="text" name="apresentador" maxlength="160"></label>
    <label class="campo">Descrição<textarea name="descricao" rows="3"></textarea></label>
    <div class="linha2">
      <label class="campo">Cor<input type="color" name="cor" value="#e3161b"></label>
      <div class="cores-rapidas" id="coresRapidas"></div>
    </div>
    <?= campo_imagem('foto', null, 'Foto do programa / apresentador', 'Arraste a foto ou clique') ?>
    <div class="modal-acoes">
      <button type="button" class="btn btn-perigo" id="excluirPrograma"><?= icone('trash') ?> Excluir</button>
      <span class="espaco"></span>
      <button type="button" class="btn btn-fantasma" data-fechar>Cancelar</button>
      <button type="submit" class="btn btn-primario" value="salvar"><?= icone('check') ?> Salvar</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="modalHorario">
  <form method="dialog" class="modal-corpo" id="formHorario">
    <h2>Horário na grade</h2>
    <input type="hidden" name="id">
    <label class="campo">Programa<select name="programa_id" id="selPrograma"></select></label>
    <label class="campo">Dia<select name="dia"><?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><option value="<?= $d ?>"><?= DIAS[$d] ?></option><?php endforeach; ?></select></label>
    <div class="linha2">
      <label class="campo">Início<input type="time" name="inicio" required step="300"></label>
      <label class="campo">Fim<input type="time" name="fim" required step="300"></label>
    </div>
    <p class="ajuda">Para terminar à meia-noite use 00:00 no fim.</p>
    <div class="modal-acoes">
      <button type="button" class="btn btn-perigo" id="excluirHorario"><?= icone('trash') ?> Remover da grade</button>
      <span class="espaco"></span>
      <button type="button" class="btn btn-fantasma" data-fechar>Cancelar</button>
      <button type="submit" class="btn btn-primario" value="salvar"><?= icone('check') ?> Salvar</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="modalCopiar">
  <form method="dialog" class="modal-corpo" id="formCopiar">
    <h2>Copiar a programação de um dia</h2>
    <label class="campo">Copiar de<select name="de"><?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><option value="<?= $d ?>"><?= DIAS[$d] ?></option><?php endforeach; ?></select></label>
    <fieldset class="campo dias-check"><legend class="rotulo">Para (substitui a grade desses dias)</legend>
      <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?><label class="check"><input type="checkbox" name="para" value="<?= $d ?>"> <?= DIAS[$d] ?></label><?php endforeach; ?>
    </fieldset>
    <div class="modal-acoes">
      <span class="espaco"></span>
      <button type="button" class="btn btn-fantasma" data-fechar>Cancelar</button>
      <button type="submit" class="btn btn-primario" value="copiar"><?= icone('copy') ?> Copiar</button>
    </div>
  </form>
</dialog>
<?php admin_fim(['programacao.js']);

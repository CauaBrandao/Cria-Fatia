document.addEventListener('DOMContentLoaded', function() {
    const radioInteira = document.getElementById('divisao_inteira');
    const radioMeio = document.getElementById('divisao_meio');
    const boxMeio = document.getElementById('box_meio_a_meio');

    const divSabor2 = document.getElementById('container_sabor2');
    const containerMetade2 = document.getElementById('container_metade2');

    const visualMetade1 = document.getElementById('visual_metade1');
    const visualMetade2 = document.getElementById('visual_metade2');

    const selectTamanho = document.getElementById('tamanho_id');
    const selectSabor1 = document.getElementById('sabor1_id');
    const selectSabor2 = document.getElementById('sabor2_id');
    const selectBorda = document.getElementById('borda_id');

    const precoTotalElem = document.getElementById('preco_total_display');
    const precoInputElem = document.getElementById('valor_total_input');

    function checarRegrasTamanho() {
        if (!selectTamanho) return;
        const opt = selectTamanho.options[selectTamanho.selectedIndex];
        const maxSabores = opt ? parseInt(opt.dataset.maxsabor || 1) : 1;

        if (maxSabores < 2) {
            radioInteira.checked = true;
            boxMeio.style.display = 'none';
        } else {
            boxMeio.style.display = 'inline-block';
        }
        alternarDivisao();
    }

    function alternarDivisao() {
        if (radioMeio && radioMeio.checked && boxMeio.style.display !== 'none') {
            divSabor2.style.display = 'block';
            containerMetade2.style.display = 'block';
            visualMetade1.classList.remove('full');
            visualMetade1.classList.add('left');
            visualMetade2.style.display = 'flex';
        } else {
            divSabor2.style.display = 'none';
            containerMetade2.style.display = 'none';
            visualMetade1.classList.remove('left');
            visualMetade1.classList.add('full');
            visualMetade2.style.display = 'none';
        }
        calcularPreco();
    }

    function atualizarTextoVisual() {
        const s1Nome = selectSabor1.options[selectSabor1.selectedIndex] ? selectSabor1.options[selectSabor1.selectedIndex].text.split(' - ')[0] : 'Sabor 1';
        visualMetade1.querySelector('.nome-sabor').innerText = s1Nome;

        if (radioMeio && radioMeio.checked && boxMeio.style.display !== 'none') {
            const s2Nome = selectSabor2.options[selectSabor2.selectedIndex] ? selectSabor2.options[selectSabor2.selectedIndex].text.split(' - ')[0] : 'Sabor 2';
            visualMetade2.querySelector('.nome-sabor').innerText = s2Nome;
        }
    }

    function calcularPreco() {
        let total = 0;

        const s1Opt = selectSabor1.options[selectSabor1.selectedIndex];
        const precoS1 = s1Opt ? parseFloat(s1Opt.dataset.preco || 0) : 0;

        let precoMaiorSabor = precoS1;

        if (radioMeio && radioMeio.checked && boxMeio.style.display !== 'none') {
            const s2Opt = selectSabor2.options[selectSabor2.selectedIndex];
            const precoS2 = s2Opt ? parseFloat(s2Opt.dataset.preco || 0) : 0;
            precoMaiorSabor = Math.max(precoS1, precoS2);
        }

        total += precoMaiorSabor;

        // Preço Borda
        const bordaOpt = selectBorda.options[selectBorda.selectedIndex];
        const precoBorda = bordaOpt ? parseFloat(bordaOpt.dataset.preco || 0) : 0;
        total += precoBorda;

        // Adicionais de Ingredientes Metade 1
        const adicionaisM1 = document.querySelectorAll('input[name="adicionar_m1[]"]:checked');
        adicionaisM1.forEach(cb => {
            total += parseFloat(cb.dataset.preco || 0);
        });

        // Molhos Especiais
        const molhosCheckboxes = document.querySelectorAll('input.molho-checkbox:checked, input[name="molhos[]"]:checked');
        molhosCheckboxes.forEach(cb => {
            total += parseFloat(cb.dataset.preco || 0);
        });

        // Adicionais de Ingredientes Metade 2
        if (radioMeio && radioMeio.checked && boxMeio.style.display !== 'none') {
            const adicionaisM2 = document.querySelectorAll('input[name="adicionar_m2[]"]:checked');
            adicionaisM2.forEach(cb => {
                total += parseFloat(cb.dataset.preco || 0);
            });
        }

        if (precoTotalElem) {
            precoTotalElem.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        }
        if (precoInputElem) {
            precoInputElem.value = total.toFixed(2);
        }

        atualizarTextoVisual();
    }

    if (radioInteira) radioInteira.addEventListener('change', alternarDivisao);
    if (radioMeio) radioMeio.addEventListener('change', alternarDivisao);

    if (selectTamanho) selectTamanho.addEventListener('change', checarRegrasTamanho);
    if (selectSabor1) selectSabor1.addEventListener('change', calcularPreco);
    if (selectSabor2) selectSabor2.addEventListener('change', calcularPreco);
    if (selectBorda) selectBorda.addEventListener('change', calcularPreco);

    document.querySelectorAll('.ing-adicionar').forEach(cb => {
        cb.addEventListener('change', calcularPreco);
    });

    // Executa ao inicializar
    checarRegrasTamanho();
});
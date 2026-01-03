// PEGA COORDENADAS NO MAPA -----------------------------------------------------------------------
function mapa_get_individual(cplatitude, cplongitude, servidor, latitudePadrao, longitudePadrao) {
    var width = (screen.availWidth - 100);
    var height = (screen.availHeight - 100);
    var latitudeLocal = document.getElementById("LATITUDE");
    var longitudeLocal = document.getElementById("LONGITUDE");

    if (latitudeLocal.value) {
        latitudePadrao = latitudeLocal.value;
    }

    if (longitudeLocal.value) {
        longitudePadrao = longitudeLocal.value;
    }

    window.open("src/cto/componente/maps/maps." + servidor + ".get.hhvm?cplatitude=" + cplatitude + "&cplongitude=" + cplongitude + "&latitudePadrao=" + latitudePadrao + "&longitudePadrao=" + longitudePadrao + "&cplatlong=enderecoCoordenadaNovo", "mpg_popup", "toolbar=0, location=0, directories=0, status=1, menubar=0, scrollbars=1, resizable=0, screenX=0, screenY=0, left=0, top=0, width=" + width + ", height=" + height);

    return true;
}

$('input[type=text]').on('keydown', function (e) {
    if (e.which == 13) {
        e.preventDefault();
    }
});

/*FILTRO DE GRID*/
$(document).ready(function () {
    $("#buscarModelo").on("keyup", function () {
        var value = $(this).val().toLowerCase();

        //INICIO FILTRO
        var opcao = $('#buscarFiltro option:selected').val(value).text();

        if (opcao == "Portas livres") {
            $("#tableModelo tr").filter(function () {
                $(this).toggle($(this).find("td[id='id_livres']").text().toLowerCase().indexOf(value) > -1)
            });
        } else {
            $("#tableModelo tr").filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        }

        // DESMARCA TODOS OS CHECKBOX AO APLICAR O FILTRO
        $('#checkAll').prop('checked', false);
        var els = document.getElementsByName('caixas[]');
        for (var i = 0; i < els.length; i++) {
            els[i].checked = false;
        }
        //FIM FILTRO
    });

    /*ADICIONAR UM CAIXA*/
    $("#run_adicionar").on("click", function () {
        document.getElementById('formAdicionar').submit();
    });

    /*BACKUP*/
    $("#run_backup").on("click", function () {
        document.getElementById('formBackup').submit();
    });

    /*Sincronizar*/
    $("#run_sincronizar").on("click", function () {
        $.getJSON('src/cto/componente/inicio/controller.json.hhvm?search=', {
            funcao: 'consultar',
        }, function (response) {
            if (response.ativoConsulta == '0') {
                alert('Nao existem caixas para migrar!');
            } else {
                if (confirm("Existem caixas disponiveis para migrar. Confirmar a migracao?")) {
                    $.getJSON('src/cto/componente/inicio/controller.json.hhvm?search=', {
                        funcao: 'migrar',
                    }, function (response) {
                        if (response.ativoMigrado == '1') {
                            alert('Migracao realizada com sucesso!');
                            document.getElementById('formOrdemCliente').submit()
                        }
                    });
                }
            }
        });

    });

    /*Buscar Coordenadas*/
    $("#run_buscar_coord").on("click", function () {
        window.location.href = 'src/cto/componente/coordenadas/index.hhvm';
    });

    /*DELETAR TODOS OS CAIXAS SELECIONADOS*/
    $("#run_deletar").on("click", function () {
        existeCaixa = 0;

        var els = document.getElementsByName('caixas[]');

        for (var i = 0; i < els.length; i++) {
            if (els[i].checked) {
                existeCaixa = 1;
            }
        }
        if (existeCaixa) {
            if (window.confirm("Voce quer realmente excluir a(s) caixa(s) selecionada(s)?")) {

                var parent = document.getElementById("formExcluirTodos");

                for (var i = 0; i < els.length; i++) {
                    if (els[i].checked) {
                        var input = document.createElement("input");
                        input.setAttribute('type', 'hidden');
                        input.setAttribute('name', 'IDAll[]');
                        input.setAttribute('value', els[i].id.substring(5, (els[i].id.length)));

                        parent.appendChild(input);
                    }
                }

                document.getElementById('formExcluirTodos').submit();
            }
        } else {
            alert('Nenhuma caixa selecionada!');
        }

    });


    // EDITA OLT
    //var iconeEditar = document.getElementById('iconeEditar');
    //iconeEditar.addEventListener('click', function() {
    $("#iconeEditar").on("click", function () {
        var olt = document.getElementById('OLT').value;

        if (olt == 'Nenhuma') {
            Swal.fire({
                title: 'Error!',
                text: 'O OLT ' + olt + ' não pode ser usado, escolha outra opção!',
                icon: 'error',
                confirmButtonText: 'Entendi'
            })
        } else {
            $.getJSON('src/cto/componente/inicio/controller.json.hhvm?search=', {
                funcao: 'verificarTabelaOLTExiste'
            }, function (response) {


                if (response.ativoConsulta == 0) {
                    $.getJSON('src/cto/componente/inicio/controller.json.hhvm?search=', {
                        funcao: 'criatTabelaOLT'
                    }, function (response) {

                        if (response.ativoConsulta == 0) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'A tabela OLT não está disponível e ao tentar criar, retornou um problema. Entre em contato com o Administrador do sistema!',
                                icon: 'error',
                                confirmButtonText: 'Entendi'
                            })
                        } else {
                            Swal.fire({
                                title: 'success!',
                                text: 'A tabela OLT não estava disponível e foi preciso criar no banco de dados, faça a ação novamente para que você continue usando seu serviço!',
                                icon: 'success',
                                confirmButtonText: 'Entendi'
                            })
                        }

                    });
                } else {
                    $.getJSON('src/cto/componente/inicio/controller.json.hhvm?search=', {
                        funcao: 'consultarMpOlt',
                        olt: olt
                    }, function (response) {

                        if (response.ativoConsulta == 0) {
                            var parent = document.getElementById("formAdicionarOlt");

                            var input = document.createElement("input");
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', 'olt');
                            input.setAttribute('value', olt);

                            parent.appendChild(input);

                            document.getElementById('formAdicionarOlt').submit();
                        } else {
                            var parent = document.getElementById("formEditarOlt");

                            var input = document.createElement("input");
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', 'olt');
                            input.setAttribute('value', olt);

                            parent.appendChild(input);

                            document.getElementById('formEditarOlt').submit();
                        }

                    });
                }

            });
        }

        //alert("Você está editando a OLT: "+$olt+"\nFALTA IMPLEMENTAR:"+"\nVerificar se a tabela mp_olt existe"+"\nSe nao existe criar a tabela"+"\nConsultar a OLT na tabela mp_olt"+"\nSe encontrar carregar os dados para alteracao no formulario"+"\nSe nao achar carregar o formulario para adicionar utilizando o mesmo nome que veio do select"+"\nas funcoes de contultar olt, verificar se a tabela existe e criar tabela ja estao ok");
    });

    /*MOSTRAR CAIXAS NO MAPA*/
    // Quando o elemento com o id "run_show_mapa" é clicado
    $("#run_show_mapa").on("click", function () {

        // Inicializa uma variável chamada existeCaixa com o valor 0
        existeCaixa = 0;

        // Obtém uma coleção de elementos com o nome 'caixas[]'
        var els = document.getElementsByName('caixas[]');

        // Itera sobre os elementos obtidos
        for (var i = 0; i < els.length; i++) {
            // Se um elemento estiver marcado, define existeCaixa como 1
            if (els[i].checked) {
                existeCaixa = 1;
            }
        }

        // Se pelo menos um elemento estiver marcado
        if (existeCaixa) {

            // Configura o atributo 'action' do formulário com o caminho do destino
            jQuery("#formMapsTodos").attr('action', "src/cto/componente/maps/maps.google.hhvm");
            // Configura o atributo 'target' do formulário com o nome da janela de destino
            jQuery("#formMapsTodos").attr('target', "win_google");
            // Abre uma nova janela com dimensões específicas
            fabrewin((screen.availWidth - 100), (screen.availHeight - 100), 'win_google');

            // Obtém o elemento pai do formulário pelo ID
            var parent = document.getElementById("formMapsTodos");

            // Remove todos os elementos filhos do formulário
            $('#formMapsTodos').empty();

            // Itera sobre os elementos 'caixas[]' novamente
            for (var i = 0; i < els.length; i++) {
                // Se um elemento estiver marcado
                if (els[i].checked) {
                    // Cria um elemento de input oculto com informações específicas e adiciona ao formulário
                    var input = document.createElement("input");
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('id', 'IdShow' + i);
                    input.setAttribute('name', 'IDAll[]');
                    input.setAttribute('value', els[i].id.substring(5, (els[i].id.length)));

                    parent.appendChild(input);
                }
            }

            // Verifica se todos os elementos estão marcados
            todosMarcados = 1;

            for (var i = 0; i < els.length; i++) {
                if (!els[i].checked) {
                    todosMarcados = 0;
                }
            }

            // Cria um elemento de input oculto indicando se todos os elementos estão marcados e adiciona ao formulário
            var input = document.createElement("input");
            input.setAttribute('type', 'hidden');
            input.setAttribute('id', 'todosMarcados');
            input.setAttribute('name', 'todosMarcados');
            input.setAttribute('value', todosMarcados);

            parent.appendChild(input);

            // Submete o formulário
            document.getElementById('formMapsTodos').submit();

        } else {
            // Se nenhum elemento estiver marcado, exibe um alerta
            alert('Nenhuma caixa selecionada!');
        }

    });

    /*EDITAR UM CAIXA*/
    $(".editar").on("click", function () {
        document.getElementById('formEditar' + this.name).submit();
    });

    /*DELETAR UM CAIXA*/
    $(".excluir").on("click", function () {
        if (window.confirm("Voce quer realmente excluir a caixa de ID " + this.name + "?")) {
            document.getElementById('formExcluir' + this.name).submit();
        }
    });

    /*SELECIONAR TODOS OS CAIXAS VISIVEIS*/
    $("#checkAll").on("click", function () {
        console.log("SELECIONAR TODOS");
        var checkAll = document.getElementById('checkAll');
        var els = document.getElementsByName('caixas[]');
        for (var i = 0; i < els.length; i++) {
            if ($('#' + els[i].id).is(":visible")) {
                els[i].checked = checkAll.checked;
            }
        }
    });

});


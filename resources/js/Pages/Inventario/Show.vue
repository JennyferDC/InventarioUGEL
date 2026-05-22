<script setup>
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import QrcodeVue from "qrcode.vue";
import ModalFichaTecnica from "./Partials/ModalFichaTecnica.vue";
import PersonaCrearModal from "@/Pages/Personas/Partials/ModaCrear.vue";
import axios from "axios";

const props = defineProps({
    equipo: {
        type: Object,
        required: true,
    },
    otrosEquipos: {
        type: Array,
        default: () => [],
    },
    personas: {
        type: Array,
        required: true,
    },
    areas: {
        type: Array,
        required: true,
    },
    programasDisponibles: {
        type: Array,
        default: () => [],
    },
});

const TIPOS_POR_CATEGORIA = {
    equipo: ["PC", "Laptop", "Todo en uno", "Monitor", "Teclado", "Mouse", "Otro (equipos)"],
    programa: ["Institucional", "Navegador", "Ofimática", "Soporte", "Antivirus", "Otro (programas)"]
};

const tiposDisponibles = computed(() => {
    const cat = (form.categoria || 'equipo').toLowerCase();
    return TIPOS_POR_CATEGORIA[cat] || [];
});

const ESTADOS_EQUIPO = ["LIBRE", "EN USO", "BAJA"];

const form = useForm({
    id: props.equipo.id,
    cod_informatica: props.equipo.cod_informatica,
    cod_patrimonial: props.equipo.cod_patrimonial ?? "",
    nombre: props.equipo.nombre ?? "",
    nombre_usuario: props.equipo.nombre_usuario ?? "",
    tipo: props.equipo.tipo,
    estado: props.equipo.estado,
    fecha_ingreso: props.equipo.fecha_ingreso,
    fecha_disponible_uso: props.equipo.fecha_disponible_uso,
    vida_util_anios: props.equipo.vida_util_anios,
    id_persona: props.equipo.id_persona ?? "",
    observacion_tecnica: props.equipo.observacion_tecnica ?? "",
    categoria: props.equipo.categoria ?? "equipo",
    ip: props.equipo.ip ?? "",
    clasificacion: props.equipo.clasificacion ?? "",
    caracteristicas: Array.isArray(props.equipo.caracteristicas)
        ? props.equipo.caracteristicas.map((c) => ({ ...c }))
        : [],
});

const showSuccess = ref(false);
const showModalFicha = ref(false);
const linkCopiado = ref(false);

const showHistorialDrawer = ref(false);
const historial = ref([]);
const loadingHistorial = ref(false);

const fetchHistorial = async () => {
    loadingHistorial.value = true;
    try {
        const response = await axios.get(route('equipos.historial', form.id));
        historial.value = response.data.data;
    } catch (error) {
        console.error("Error al obtener el historial de cambios:", error);
    } finally {
        loadingHistorial.value = false;
    }
};

const getDescripcionLines = (descripcion) => {
    if (!descripcion) return [];
    return descripcion.split('\n').map(l => l.trim()).filter(Boolean);
};

const openHistorialDrawer = () => {
    showHistorialDrawer.value = true;
    fetchHistorial();
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleString("es-ES", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
};

const searchPersona = ref("");
const showPersonaDropdown = ref(false);

// Local responsive copy of personas to allow dynamic addition
const localPersonas = ref([...props.personas]);
const oficinas = ref([]);
const loadingOficinas = ref(false);
const showQuickRegister = ref(false);
const savingPersona = ref(false);

const fetchOficinas = async () => {
    if (oficinas.value.length > 0) return;
    loadingOficinas.value = true;
    try {
        const response = await axios.get(route('api.oficinas.index'));
        oficinas.value = response.data.data;
    } catch (error) {
        console.error("Error al obtener oficinas:", error);
    } finally {
        loadingOficinas.value = false;
    }
};

const openQuickRegister = async () => {
    await fetchOficinas();
    showQuickRegister.value = true;
};

const savePersona = async (payload) => {
    savingPersona.value = true;
    try {
        const response = await axios.post(route("personas.store"), payload);
        if (response.data?.data) {
            const nuevaPersona = response.data.data;
            
            // Add new persona to the local reactive list
            localPersonas.value.push(nuevaPersona);
            
            // Auto select the newly created persona
            selectPersona(nuevaPersona);
            
            showQuickRegister.value = false;
        }
    } catch (error) {
        console.error("Error al registrar persona:", error);
        alert(error.response?.data?.message || "No se pudo registrar a la persona.");
    } finally {
        savingPersona.value = false;
    }
};

watch(() => props.personas, (newVal) => {
    localPersonas.value = [...newVal];
}, { deep: true });

const normalizeText = (text) => text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

const filteredPersonas = computed(() => {
    const activePersonas = localPersonas.value.filter(p => p.estado === 'ACTIVO' || !p.estado);
    const q = normalizeText(searchPersona.value);
    if (!q) return activePersonas.slice(0, 50);
    return activePersonas.filter(p => {
        const text = normalizeText(`${p.nombre_completo} ${p.oficina?.nombre || ''} ${p.oficina?.area?.nombre || ''}`);
        return text.includes(q);
    }).slice(0, 50);
});

const selectPersona = (p) => {
    if (p) {
        form.id_persona = p.id;
        searchPersona.value = p.nombre_completo;
    } else {
        form.id_persona = "";
        searchPersona.value = "";
    }
    showPersonaDropdown.value = false;
};

const handlePersonaBlur = () => {
    setTimeout(() => {
        showPersonaDropdown.value = false;
    }, 200);
};

watch(() => form.id_persona, (val) => {
    if (val) {
        const p = localPersonas.value.find(x => x.id === val);
        if (p) {
            searchPersona.value = p.nombre_completo;
        }
        if (form.estado !== "BAJA") {
            form.estado = "EN USO";
        }
    } else {
        searchPersona.value = "";
        if (form.estado !== "BAJA") {
            form.estado = "LIBRE";
        }
    }
}, { immediate: true });

watch(() => form.tipo, (newTipo) => {
    if (!['pc', 'laptop', 'todo en uno'].includes((newTipo || '').toLowerCase())) {
        form.ip = "";
    }
});

watch(() => form.estado, (newEstado) => {
    if (newEstado !== 'BAJA') {
        form.observacion_tecnica = "";
    }
});

const copiarLink = () => {
    navigator.clipboard.writeText(currentUrl.value);
    linkCopiado.value = true;
    setTimeout(() => {
        linkCopiado.value = false;
    }, 2000);
};

const downloadReporteAxios = async (tipo_reporte, filtros) => {
    try {
        const response = await axios.post(route('reportes.equipos.pdf'), {
            tipo_reporte,
            filtros
        }, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        let filename = 'reporte.pdf';
        const disposition = response.headers['content-disposition'];
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) { 
                filename = matches[1].replace(/['"]/g, '');
            }
        }
        
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Error downloading PDF", error);
    }
};

const handleDownloadFicha = (opciones) => {
    downloadReporteAxios('ficha_tecnica', {
        equipo_id: form.id,
        incluir_datos_responsable: opciones.incluir_datos_responsable ? 1 : 0,
        incluir_otros_equipos: opciones.incluir_otros_equipos ? 1 : 0,
    });
};

const handleDownloadPersona = () => {
    if (!props.equipo.persona) return;
    downloadReporteAxios('inventario_persona', {
        persona_id: props.equipo.persona.id
    });
};

const handleSubmit = () => {
    if (form.categoria === 'programa') {
        form.estado = null;
        form.fecha_ingreso = null;
        form.fecha_disponible_uso = null;
        form.vida_util_anios = null;
        form.id_persona = null;
        form.ip = null;
        form.clasificacion = null;
        form.observacion_tecnica = null;
    }

    form.put(route("equipos.update", form.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
            if (showHistorialDrawer.value) {
                fetchHistorial();
            }
        },
    });
};

const darDeBaja = () => {
    form.estado = "BAJA";
    form.id_persona = ""; // Liberar equipo
};

const restaurarEquipo = () => {
    form.estado = form.id_persona ? "EN USO" : "LIBRE";
};

const agregarCaracteristica = () => {
    form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    form.caracteristicas.splice(index, 1);
};

const loadingIA = ref(false);
const mejorarObservacionConIA = async () => {
    if (!form.observacion_tecnica || !form.observacion_tecnica.trim()) return;

    loadingIA.value = true;
    try {
        const response = await axios.post(route('api.ai.mejorar-observacion'), {
            observacion: form.observacion_tecnica
        });
        if (response.data && response.data.success && response.data.resultado) {
            form.observacion_tecnica = response.data.resultado;
        } else {
            alert('No se pudo mejorar la observación. Inténtelo de nuevo.');
        }
    } catch (error) {
        console.error("Error al mejorar la observación con IA:", error);
        alert(error.response?.data?.message || 'Error al conectar con el servicio de IA. Verifique su conexión o intente más tarde.');
    } finally {
        loadingIA.value = false;
    }
};

const currentUrl = computed(() => window.location.href);

const getEstadoClass = (estado) => {
    switch (estado) {
        case "LIBRE":
            return "bg-green-100 text-green-800";
        case "EN USO":
            return "bg-blue-100 text-blue-800";
        case "BAJA":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const downloadQr = () => {
    const canvas = document.querySelector("#qr-container canvas");
    if (canvas) {
        const link = document.createElement("a");
        link.download = `QR-${form.cod_informatica}.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
    }
};

// AI Diagnostic Drawer States
const showAIDrawer = ref(false);
const loadingDiag = ref(false);
const problemaText = ref("");
const diagnosticoResultado = ref("");
const diagnosticoProveedor = ref("");
const diagnosticoModelo = ref("");
const copyDiagCopiado = ref(false);

const suggestions = [
    "El equipo presenta lentitud extrema",
    "El ventilador hace ruido y se apaga",
    "Conflicto de dirección IP / Red",
    "Pantalla azul de error (BSOD) constante"
];

const selectSugerencia = (sug) => {
    if (!problemaText.value) {
        problemaText.value = sug;
    } else if (problemaText.value.endsWith('.')) {
        problemaText.value += " " + sug;
    } else if (problemaText.value.endsWith('. ') || problemaText.value.endsWith(' ')) {
        problemaText.value += sug;
    } else {
        problemaText.value += ". " + sug;
    }
};

const openAIDrawer = () => {
    showAIDrawer.value = true;
};

const generarDiagnostico = async () => {
    if (!problemaText.value || !problemaText.value.trim()) return;

    loadingDiag.value = true;
    diagnosticoResultado.value = "";
    diagnosticoProveedor.value = "";
    diagnosticoModelo.value = "";
    try {
        const response = await axios.post(route('api.ai.diagnosticar-equipo'), {
            equipo_id: form.id,
            problema: problemaText.value
        });
        if (response.data && response.data.success && response.data.resultado) {
            diagnosticoResultado.value = response.data.resultado;
            diagnosticoProveedor.value = response.data.provider || "Gemini";
            diagnosticoModelo.value = response.data.model || "gemini-2.5-flash";
        } else {
            alert('No se pudo generar el diagnóstico. Inténtelo de nuevo.');
        }
    } catch (error) {
        console.error("Error al generar el diagnóstico con IA:", error);
        alert(error.response?.data?.message || 'Error al conectar con el servicio de diagnóstico por IA.');
    } finally {
        loadingDiag.value = false;
    }
};

const copiarDiagnostico = () => {
    if (!diagnosticoResultado.value) return;
    navigator.clipboard.writeText(diagnosticoResultado.value);
    copyDiagCopiado.value = true;
    setTimeout(() => {
        copyDiagCopiado.value = false;
    }, 2000);
};

const aplicarAObservacion = () => {
    if (!diagnosticoResultado.value) return;
    form.estado = 'BAJA';
    form.observacion_tecnica = diagnosticoResultado.value;
};

const formatMarkdown = (text) => {
    if (!text) return "";
    
    // Check if it is a factual greeting message
    let isFactualGreeting = text.includes("no corresponde a una descripción de falla técnica");
    
    // Escape HTML to prevent XSS
    let html = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Convert bold **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');

    // Convert italic *text*
    html = html.replace(/\*(.*?)\*/g, '<em class="italic text-gray-800">$1</em>');

    // Parse lines one by one for lists and titles
    const lines = html.split('\n');
    let output = [];
    let inList = false;
    let listType = null; // 'ul' or 'ol'

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i];

        // Headers
        if (line.startsWith('### ')) {
            if (inList) {
                output.push(`</${listType}>`);
                inList = false;
                listType = null;
            }
            const headerText = line.substring(4);
            output.push(`<h4 class="text-xs font-extrabold uppercase tracking-wider text-purple-950 mt-5 mb-2.5 flex items-center gap-2 border-l-2 border-purple-500 pl-2">${headerText}</h4>`);
            continue;
        }
        if (line.startsWith('## ')) {
            if (inList) {
                output.push(`</${listType}>`);
                inList = false;
                listType = null;
            }
            const headerText = line.substring(3);
            output.push(`<h3 class="text-sm font-extrabold text-indigo-950 mt-6 mb-3 border-b border-indigo-100 pb-1.5 flex items-center gap-2">${headerText}</h3>`);
            continue;
        }
        if (line.startsWith('# ')) {
            if (inList) {
                output.push(`</${listType}>`);
                inList = false;
                listType = null;
            }
            const headerText = line.substring(2);
            output.push(`<h2 class="text-base font-black text-indigo-950 mt-8 mb-4 border-b-2 border-indigo-200 pb-2">${headerText}</h2>`);
            continue;
        }

        // Bullet Lists
        let bulletMatch = line.match(/^\s*[-*]\s+(.+)$/);
        if (bulletMatch) {
            if (!inList || listType !== 'ul') {
                if (inList) output.push(`</${listType}>`);
                output.push('<ul class="space-y-1.5 my-3 pl-5 list-disc text-gray-700">');
                inList = true;
                listType = 'ul';
            }
            output.push(`<li class="text-[12px] leading-relaxed">${bulletMatch[1]}</li>`);
            continue;
        }

        // Numbered Lists
        let numberedMatch = line.match(/^\s*(\d+)\.\s+(.+)$/);
        if (numberedMatch) {
            if (!inList || listType !== 'ol') {
                if (inList) output.push(`</${listType}>`);
                output.push('<ol class="space-y-1.5 my-3 pl-5 list-decimal text-gray-700">');
                inList = true;
                listType = 'ol';
            }
            output.push(`<li class="text-[12px] leading-relaxed"><span class="font-medium text-gray-800">${numberedMatch[2]}</span></li>`);
            continue;
        }

        // Empty line
        if (line.trim() === '') {
            if (inList) {
                output.push(`</${listType}>`);
                inList = false;
                listType = null;
            }
            continue;
        }

        // Regular paragraph line
        if (inList) {
            output.push(`</${listType}>`);
            inList = false;
            listType = null;
        }
        output.push(`<p class="text-[12px] text-gray-600 leading-relaxed my-2">${line}</p>`);
    }

    if (inList) {
        output.push(`</${listType}>`);
    }

    let parsedHtml = output.join('\n');

    // Accentuate greeting/factual responses
    if (isFactualGreeting) {
        parsedHtml = parsedHtml.replace(
            /<p class="text-\[12px\] text-gray-600 leading-relaxed my-2">([^<]*?no\s+corresponde\s+a\s+una\s+descripción\s+de\s+falla\s+técnica[^]*?)<\/p>/i,
            '<div class="bg-amber-50 border-l-4 border-amber-500 p-3.5 rounded-r-xl text-[12px] text-amber-800 mb-4 font-medium flex items-start gap-2.5 shadow-sm">' +
            '<svg class="size-4.5 shrink-0 mt-0.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' +
            '</svg>' +
            '<div>$1</div>' +
            '</div>'
        );
    }

    return parsedHtml;
};

// --- SOFTWARE / PROGRAMAS MANAGEMENT LOGIC ---
const localProgramas = ref([...(props.equipo.programas || [])]);
const showModalProgramas = ref(false);
const searchPrograma = ref("");
const selectedProgramasLeft = ref([]);
const selectedProgramasRight = ref([]);
const savingProgramas = ref(false);

watch(() => props.equipo.programas, (newVal) => {
    localProgramas.value = [...(newVal || [])];
}, { deep: true });

const leftPrograms = computed(() => {
    return props.programasDisponibles.filter(
        p => !localProgramas.value.some(lp => lp.id === p.id)
    );
});

const filteredLeftPrograms = computed(() => {
    const q = searchPrograma.value.trim().toLowerCase();
    if (!q) return leftPrograms.value;
    return leftPrograms.value.filter(p => 
        (p.nombre && p.nombre.toLowerCase().includes(q)) || 
        (p.cod_informatica && p.cod_informatica.toLowerCase().includes(q)) ||
        (p.tipo && p.tipo.toLowerCase().includes(q))
    );
});

const toggleSelectLeft = (program) => {
    const idx = selectedProgramasLeft.value.findIndex(p => p.id === program.id);
    if (idx > -1) {
        selectedProgramasLeft.value.splice(idx, 1);
    } else {
        selectedProgramasLeft.value.push(program);
    }
};

const toggleSelectRight = (program) => {
    const idx = selectedProgramasRight.value.findIndex(p => p.id === program.id);
    if (idx > -1) {
        selectedProgramasRight.value.splice(idx, 1);
    } else {
        selectedProgramasRight.value.push(program);
    }
};

const isSelectedLeft = (program) => {
    return selectedProgramasLeft.value.some(p => p.id === program.id);
};

const isSelectedRight = (program) => {
    return selectedProgramasRight.value.some(p => p.id === program.id);
};

const transferToRight = (programs) => {
    programs.forEach(prog => {
        if (!localProgramas.value.some(lp => lp.id === prog.id)) {
            localProgramas.value.push(prog);
        }
    });
    selectedProgramasLeft.value = [];
};

const transferToLeft = (programs) => {
    localProgramas.value = localProgramas.value.filter(
        lp => !programs.some(p => p.id === lp.id)
    );
    selectedProgramasRight.value = [];
};

const addSelectedToRight = () => {
    transferToRight(selectedProgramasLeft.value);
};

const removeSelectedFromRight = () => {
    transferToLeft(selectedProgramasRight.value);
};

const addAllToRight = () => {
    transferToRight(filteredLeftPrograms.value);
};

const removeAllFromRight = () => {
    transferToLeft([...localProgramas.value]);
};

const openModalProgramas = () => {
    searchPrograma.value = "";
    selectedProgramasLeft.value = [];
    selectedProgramasRight.value = [];
    showModalProgramas.value = true;
};

const saveProgramas = async () => {
    savingProgramas.value = true;
    try {
        const response = await axios.post(route('equipos.updateProgramas', props.equipo.id), {
            programa_ids: localProgramas.value.map(p => p.id)
        });
        if (response.data && response.data.data) {
            localProgramas.value = response.data.data.programas || [];
            showModalProgramas.value = false;
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
        }
    } catch (error) {
        console.error("Error al guardar programas:", error);
        alert(error.response?.data?.message || "No se pudieron guardar los programas del equipo.");
    } finally {
        savingProgramas.value = false;
    }
};
</script>

<template>
    <AppLayout title="Detalle del Equipo">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('equipos.index')"
                        class="rounded-full p-2 hover:bg-gray-200 transition text-gray-600"
                    >
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-ugel-guinda flex items-center gap-2">
                        <span>{{ form.cod_informatica }}</span>
                        <span class="inline-flex items-center rounded-md bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700 uppercase tracking-wide">
                            {{ form.categoria || 'equipo' }}
                        </span>
                    </h2>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-purple-50 px-4 py-2.5 text-sm font-semibold text-purple-700 shadow-sm hover:bg-purple-100 transition-all duration-200"
                        @click="openAIDrawer"
                    >
                        <svg class="size-4 animate-pulse text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.187.904zM18 7.5l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5zM21 16l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5z" />
                        </svg>
                        Diagnóstico de IA
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                        @click="showModalFicha = true"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                        @click="openHistorialDrawer"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Historial
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing || !form.isDirty"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-ugel-guinda disabled:opacity-50 disabled:bg-gray-400 transition-all duration-200"
                        @click="handleSubmit"
                    >
                        <svg v-if="form.processing" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Notificación de éxito -->
                <transition
                    enter-active-class="transform transition duration-300 ease-out"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showSuccess" class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    El equipo ha sido actualizado correctamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Lado izquierdo: Formulario de edición (similar al ModalEditar) -->
                    <div class="lg:col-span-2 bg-white shadow-xl rounded-2xl overflow-hidden">
                        <div class="border-b border-ugel-azul/10 px-6 py-5 bg-gray-50">
                            <h3 class="text-lg font-bold text-ugel-azul">Información y Edición</h3>
                            <p class="mt-1 text-sm text-gray-500">Modifica los detalles del equipo aquí.</p>
                        </div>
                        <div class="px-6 py-6">
                            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                                Revisa los campos para corregir los errores.
                            </div>

                            <form @submit.prevent="handleSubmit" class="space-y-6">
                                <!-- VISTA PARA PROGRAMAS -->
                                <div v-if="form.categoria === 'programa'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Código informática (deshabilitado, autogenerado) -->
                                    <div>
                                        <label for="prog_cod" class="block text-sm font-medium text-gray-700">Código informática</label>
                                        <input
                                            id="prog_cod"
                                            v-model="form.cod_informatica"
                                            type="text"
                                            disabled
                                            class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                        />
                                    </div>

                                    <!-- Nombre del programa -->
                                    <div>
                                        <label for="prog_nombre" class="block text-sm font-medium text-gray-700">Nombre del programa <span class="text-red-500">*</span></label>
                                        <input
                                            id="prog_nombre"
                                            v-model="form.nombre"
                                            type="text"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                            placeholder=""
                                        />
                                        <div v-if="form.errors.nombre" class="mt-1 text-xs text-red-500">{{ form.errors.nombre }}</div>
                                    </div>

                                    <!-- Tipo de programa -->
                                    <div class="md:col-span-2">
                                        <label for="prog_tipo" class="block text-sm font-medium text-gray-700">Tipo de programa <span class="text-red-500">*</span></label>
                                        <select
                                            id="prog_tipo"
                                            v-model="form.tipo"
                                            required
                                            :disabled="true"
                                            class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                        >
                                            <option value="" disabled>Seleccione un tipo</option>
                                            <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                        </select>
                                        <div v-if="form.errors.tipo" class="mt-1 text-xs text-red-500">{{ form.errors.tipo }}</div>
                                    </div>
                                </div>

                                <!-- VISTA PARA EQUIPOS (SECCIONADA) -->
                                <div v-else class="space-y-6">
                                    <!-- Sección 1: Información General -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            1. Información General del Equipo
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Código informática (deshabilitado, autogenerado) -->
                                            <div>
                                                <label for="equipo_cod" class="block text-sm font-medium text-gray-700">Código informática</label>
                                                <input
                                                    id="equipo_cod"
                                                    v-model="form.cod_informatica"
                                                    type="text"
                                                    disabled
                                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                                />
                                            </div>

                                            <!-- Código patrimonial -->
                                            <div>
                                                <label for="eq_patrimonial" class="block text-sm font-medium text-gray-700">Código patrimonial</label>
                                                <input
                                                    id="eq_patrimonial"
                                                    v-model="form.cod_patrimonial"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="74089900XXXX"
                                                />
                                                <div v-if="form.errors.cod_patrimonial" class="mt-1 text-xs text-red-500">{{ form.errors.cod_patrimonial }}</div>
                                            </div>

                                            <!-- Nombre de equipo -->
                                            <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                                <label for="eq_nombre" class="block text-sm font-medium text-gray-700">Nombre de equipo</label>
                                                <input
                                                    id="eq_nombre"
                                                    v-model="form.nombre"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="DESKTOP-EAOR902..."
                                                />
                                                <div v-if="form.errors.nombre" class="mt-1 text-xs text-red-500">{{ form.errors.nombre }}</div>
                                            </div>

                                            <!-- Tipo de equipo -->
                                            <div :class="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase()) ? '' : 'md:col-span-2'">
                                                <label for="equipo_tipo" class="block text-sm font-medium text-gray-700">Tipo de equipo <span class="text-red-500">*</span></label>
                                                <select
                                                    id="equipo_tipo"
                                                    v-model="form.tipo"
                                                    required
                                                    :disabled="true"
                                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                                >
                                                    <option value="" disabled>Seleccione un tipo</option>
                                                    <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                                </select>
                                                <div v-if="form.errors.tipo" class="mt-1 text-xs text-red-500">{{ form.errors.tipo }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección 2: Estado y Asignación -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            2. Estado y Asignación de Responsable
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Estado -->
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-medium text-gray-700">Estado <span class="text-red-500">*</span></label>
                                                    <button 
                                                        type="button" 
                                                        v-if="form.estado !== 'BAJA'"
                                                        @click="darDeBaja"
                                                        class="text-xs text-red-600 hover:text-red-800 font-semibold"
                                                    >
                                                        Dar de baja
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        v-else
                                                        @click="restaurarEquipo"
                                                        class="text-xs text-green-600 hover:text-green-800 font-semibold"
                                                    >
                                                        Restaurar equipo
                                                    </button>
                                                </div>
                                                <div class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm flex items-center">
                                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', 
                                                        form.estado === 'LIBRE' ? 'bg-green-100 text-green-700 border-green-200' :
                                                        form.estado === 'EN USO' ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                                        'bg-red-100 text-red-700 border-red-200']">
                                                        {{ form.estado }}
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-1 leading-tight">
                                                    El estado se actualiza automáticamente al asignar o quitar un responsable.
                                                </p>
                                            </div>

                                            <!-- Cuenta -->
                                            <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                                <label for="eq_usuario" class="block text-sm font-medium text-gray-700">Cuenta local</label>
                                                <input
                                                    id="eq_usuario"
                                                    v-model="form.nombre_usuario"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="JUANPEREZ, UHNEXUSB..."
                                                />
                                                <div v-if="form.errors.nombre_usuario" class="mt-1 text-xs text-red-500">{{ form.errors.nombre_usuario }}</div>
                                            </div>

                                            <!-- Responsable -->
                                            <div class="md:col-span-2 relative">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label for="search_persona" class="block text-sm font-medium text-gray-700">Responsable</label>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1 text-xs font-semibold text-ugel-azul hover:text-ugel-guinda transition"
                                                        @click="openQuickRegister"
                                                        v-if="form.estado !== 'BAJA'"
                                                    >
                                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                        Registro rápido
                                                    </button>
                                                </div>
                                                <input
                                                    id="search_persona"
                                                    v-model="searchPersona"
                                                    type="text"
                                                    autocomplete="off"
                                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul disabled:bg-gray-100 disabled:text-gray-400 cursor-text"
                                                    placeholder="Buscar persona por nombre..."
                                                    @focus="showPersonaDropdown = true"
                                                    @blur="handlePersonaBlur"
                                                    :disabled="form.estado === 'BAJA'"
                                                />
                                                <div
                                                    v-if="showPersonaDropdown"
                                                    class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                                                >
                                                    <div
                                                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        @click="selectPersona(null)"
                                                    >
                                                        -- Sin asignar --
                                                    </div>
                                                    <div
                                                        v-for="p in filteredPersonas"
                                                        :key="p.id"
                                                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between"
                                                        @click="selectPersona(p)"
                                                    >
                                                        <div>
                                                            <div class="font-medium">{{ p.nombre_completo }}</div>
                                                            <div class="text-xs text-gray-500" v-if="p.oficina">{{ p.oficina.nombre }} {{ p.oficina.area ? ' - ' + p.oficina.area.nombre : '' }}</div>
                                                        </div>
                                                        <div class="size-2 rounded-full bg-green-500 shrink-0" title="Activo"></div>
                                                    </div>
                                                    <div v-if="filteredPersonas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                                        No se encontraron resultados
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Observación técnica de baja -->
                                            <div v-if="form.estado === 'BAJA'" class="md:col-span-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label for="equipo_observacion" class="block text-sm font-medium text-gray-700">Observación técnica de baja</label>
                                                    <button
                                                        type="button"
                                                        @click="mejorarObservacionConIA"
                                                        :disabled="loadingIA || !form.observacion_tecnica.trim()"
                                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-purple-700 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 px-2.5 py-1 rounded-md border border-purple-200 transition-all duration-200 disabled:opacity-40 disabled:bg-gray-50 disabled:text-gray-400 disabled:border-gray-200 shadow-sm"
                                                        title="Optimizar texto usando IA"
                                                    >
                                                        <svg v-if="loadingIA" class="size-3.5 animate-spin text-purple-600" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <svg v-else class="size-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.187.904zM18 7.5l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5zM21 16l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5z" />
                                                        </svg>
                                                        <span>{{ loadingIA ? 'Mejorando...' : 'Mejorar con IA' }}</span>
                                                    </button>
                                                </div>
                                                <textarea
                                                    id="equipo_observacion"
                                                    v-model="form.observacion_tecnica"
                                                    rows="3"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="Describa el motivo o estado técnico para dar de baja el equipo..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección 3: Especificaciones y Ciclo de Vida -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            3. Especificaciones y Ciclo de Vida
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Clasificación -->
                                            <div>
                                                <label for="equipo_clasificacion" class="block text-sm font-medium text-gray-700">Clasificación</label>
                                                <select
                                                    id="equipo_clasificacion"
                                                    v-model="form.clasificacion"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                >
                                                    <option value="">Seleccione clasificación</option>
                                                    <option value="BUENO">Bueno</option>
                                                    <option value="REGULAR">Regular</option>
                                                    <option value="MALO">Malo</option>
                                                </select>
                                            </div>

                                            <!-- Dirección IP -->
                                            <div v-if="['pc', 'laptop', 'todo en uno'].includes((form.tipo || '').toLowerCase())">
                                                <label for="equipo_ip" class="block text-sm font-medium text-gray-700">Dirección IP</label>
                                                <input
                                                    id="equipo_ip"
                                                    v-model="form.ip"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="192.168.1.XX"
                                                />
                                            </div>

                                            <!-- Vida útil (años) -->
                                            <div :class="['pc', 'laptop', 'todo en uno'].includes((form.tipo || '').toLowerCase()) ? 'md:col-span-2' : ''">
                                                <label for="vida_util" class="block text-sm font-medium text-gray-700">Vida útil (años)</label>
                                                <input
                                                    id="vida_util"
                                                    v-model="form.vida_util_anios"
                                                    type="number"
                                                    min="0"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>

                                            <!-- Fecha de ingreso -->
                                            <div>
                                                <label for="fecha_ingreso" class="block text-sm font-medium text-gray-700">Fecha de ingreso</label>
                                                <p class="text-[11px] text-gray-500 mb-1">Es cuando el área de informática recibe el equipo</p>
                                                <input
                                                    id="fecha_ingreso"
                                                    v-model="form.fecha_ingreso"
                                                    type="date"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>

                                            <!-- Disponible desde -->
                                            <div>
                                                <label for="fecha_disp" class="block text-sm font-medium text-gray-700">Disponible desde</label>
                                                <p class="text-[11px] text-gray-500 mb-1">Es cuando se comienza a utilizar el equipo</p>
                                                <input
                                                    id="fecha_disp"
                                                    v-model="form.fecha_disponible_uso"
                                                    type="date"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Características / Detalles (Comunes para Equipos y Programas) -->
                                <div class="mt-8 border-t border-gray-100 pt-6">
                                    <div class="mb-4 flex items-start justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">
                                                {{ form.categoria === 'programa' ? 'Características del programa' : 'Características / Detalles' }}
                                            </h4>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                {{ form.categoria === 'programa' ? 'Agrega detalles del software como versión, tipo de licencia, clave de activación, etc.' : 'Agrega detalles del equipo como color, modelo, RAM, procesador, etc.' }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-md bg-ugel-azul/10 px-2.5 py-1.5 text-xs font-semibold text-ugel-azul hover:bg-ugel-azul/20"
                                            @click="agregarCaracteristica"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Agregar
                                        </button>
                                    </div>

                                    <div v-if="form.caracteristicas.length === 0" class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-sm text-gray-500">
                                        No hay características adicionales.
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="(car, index) in form.caracteristicas"
                                            :key="index"
                                            class="flex items-start gap-3 rounded-lg bg-gray-50 p-3"
                                        >
                                            <div class="flex-1">
                                                <input
                                                    v-model="car.clave"
                                                    type="text"
                                                    :placeholder="form.categoria === 'programa' ? 'Ej: Versión, Licencia...' : 'Ej: Procesador, Marca...'"
                                                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                            <div class="flex-1">
                                                <input
                                                    v-model="car.valor"
                                                    type="text"
                                                    :placeholder="form.categoria === 'programa' ? 'Ej: 2021, Anual, Key-XXXX...' : 'Ej: Intel Core i7, HP...'"
                                                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                            <button
                                                type="button"
                                                class="rounded-md p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700"
                                                @click="quitarCaracteristica(index)"
                                            >
                                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lado derecho: QR y Equipos del responsable -->
                    <div class="space-y-6">
                        
                        <!-- Tarjeta QR -->
                        <div class="bg-white shadow-xl rounded-2xl overflow-hidden flex flex-col items-center justify-center p-8 text-center border-t-4 border-ugel-guinda">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Código QR del Equipo</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                Escanea este código para acceder rápidamente a esta ficha.
                            </p>
                            <div id="qr-container" class="bg-white p-4 rounded-xl shadow-inner border border-gray-100 inline-block">
                                <qrcode-vue :value="currentUrl" :size="180" level="M" render-as="canvas" />
                            </div>
                            <div class="mt-4 flex items-center justify-center gap-2 mb-6">
                                <div class="text-xs font-mono bg-gray-100 text-gray-600 px-3 py-1.5 rounded-md">
                                    {{ form.cod_informatica }}
                                </div>
                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-md transition border border-transparent"
                                    :class="linkCopiado ? 'bg-green-100 text-green-700' : 'text-ugel-azul bg-ugel-azul/10 hover:bg-ugel-azul/20'"
                                    @click="copiarLink"
                                    title="Copiar enlace"
                                >
                                    {{ linkCopiado ? 'Enlace copiado' : 'Copiar enlace' }}
                                </button>
                            </div>

                            <button
                                type="button"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-ugel-guinda px-4 py-2 text-sm font-semibold text-ugel-guinda hover:bg-ugel-guinda hover:text-white transition"
                                @click="downloadQr"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar QR
                            </button>
                        </div>

                        <!-- Tarjeta de Software / Programas Instalados -->
                        <div v-if="form.categoria === 'equipo'" class="bg-white shadow-xl rounded-2xl overflow-hidden border-t-4 border-ugel-azul">
                            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-900">Programas Asignados</h3>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200/50">
                                        {{ localProgramas.length }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-ugel-azul hover:text-ugel-guinda transition bg-ugel-azul/10 hover:bg-ugel-azul/20 px-2.5 py-1.5 rounded-md"
                                    @click="openModalProgramas"
                                >
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Gestionar
                                </button>
                            </div>
                            <div class="p-0">
                                <ul v-if="localProgramas.length > 0" class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                                    <li v-for="prog in localProgramas" :key="prog.id" class="px-6 py-3.5 hover:bg-gray-50/80 transition flex items-center justify-between group/item">
                                        <div class="flex items-center gap-3">
                                            <!-- Icono basado en el tipo de programa -->
                                            <div class="h-9 w-9 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 border border-gray-200">
                                                <!-- Antivirus -->
                                                <svg v-if="prog.tipo === 'antivirus'" class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                                <!-- Navegador -->
                                                <svg v-else-if="prog.tipo === 'navegador'" class="size-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                </svg>
                                                <!-- Ofimática / Ofimatica -->
                                                <svg v-else-if="prog.tipo === 'ofimática' || prog.tipo === 'ofimatica'" class="size-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <!-- Institucional -->
                                                <svg v-else-if="prog.tipo === 'institucional'" class="size-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <!-- Otro -->
                                                <svg v-else class="size-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 leading-snug group-hover/item:text-ugel-azul transition">{{ prog.nombre }}</p>
                                                <p class="text-xs text-gray-400 capitalize">{{ prog.tipo || 'Software' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded border border-gray-200/50">
                                                {{ prog.cod_informatica }}
                                            </span>
                                            <Link 
                                                :href="route('equipos.showByCodigo', prog.cod_informatica)" 
                                                class="text-gray-400 hover:text-ugel-azul p-1 hover:bg-gray-100 rounded transition"
                                                title="Ver detalle del programa"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </li>
                                </ul>
                                <div v-else class="px-6 py-10 text-center flex flex-col items-center justify-center">
                                    <div class="size-12 rounded-full bg-gray-50 border border-dashed border-gray-200 flex items-center justify-center text-gray-400 mb-3">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">Sin programas asignado</p>
                                    <p class="text-xs text-gray-400 max-w-[200px] mt-1 mb-4 leading-normal">Este equipo físico no tiene programas registrados aún.</p>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-ugel-azul px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-ugel-guinda transition"
                                        @click="openModalProgramas"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Asignar Software
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Equipos del Responsable -->
                        <div v-if="equipo.persona" class="bg-white shadow-xl rounded-2xl overflow-hidden">
                            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50 flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-bold text-gray-900">
                                            {{ equipo.persona.nombre_completo }}
                                        </h3>
                                        <span :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide',
                                            equipo.persona.estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ equipo.persona.estado || 'ACTIVO' }}
                                        </span>
                                    </div>
                                    <div v-if="equipo.persona.cargo" class="text-xs text-gray-500 font-medium mb-4">{{ equipo.persona.cargo }}</div>
                                    <div class="text-xs text-gray-500 flex flex-wrap items-center gap-2 mt-1">
                                        <span v-if="equipo.persona.oficina?.area" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            {{ equipo.persona.oficina.area.nombre }}
                                        </span>
                                        <span v-if="equipo.persona.celular" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ equipo.persona.celular }}
                                        </span>
                                        <span v-if="equipo.persona.correo" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ equipo.persona.correo }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="p-1.5 text-ugel-azul hover:bg-ugel-azul/10 rounded-md transition border border-transparent hover:border-ugel-azul/20"
                                    title="Descargar PDF de equipos"
                                    @click="handleDownloadPersona"
                                >
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </button>
                            </div>
                            <div class="px-6 py-3 bg-gray-50/50 border-b border-gray-100">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Otros dispositivos asignados</h4>
                            </div>
                            <div class="p-0">
                                <ul v-if="otrosEquipos.length > 0" class="divide-y divide-gray-100">
                                    <li v-for="otro in otrosEquipos" :key="otro.id" class="px-6 py-4 hover:bg-gray-50 transition cursor-pointer group/item">
                                        <Link :href="route('equipos.showByCodigo', otro.cod_informatica)" class="flex justify-between items-center group/link w-full">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 group-hover/item:text-ugel-azul group-hover/item:underline transition">{{ otro.cod_informatica }}</p>
                                                <p class="text-xs text-gray-500">{{ otro.tipo ? otro.tipo.charAt(0).toUpperCase() + otro.tipo.slice(1) : '' }}</p>
                                            </div>
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                                    getEstadoClass(otro.estado)
                                                ]"
                                            >
                                                {{ otro.estado }}
                                            </span>
                                        </Link>
                                    </li>
                                </ul>
                                <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                                    No tiene más equipos asignados.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <PersonaCrearModal
            :show="showQuickRegister"
            :oficinas="oficinas"
            :loading="savingPersona"
            @close="showQuickRegister = false"
            @save="savePersona"
        />

        <ModalFichaTecnica 
            :show="showModalFicha" 
            :equipo-id="equipo.id" 
            @close="showModalFicha = false" 
            @download="handleDownloadFicha" 
        />

        <!-- Modal para Administrar Software / Programas (Transfer Component) -->
        <div v-if="showModalProgramas" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <!-- Backdrop -->
            <transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModalProgramas = false" />
            </transition>

            <!-- Modal Content -->
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh] overflow-hidden border border-gray-100">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 bg-gray-50/50">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="size-5 text-ugel-azul" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Administrar Programas Asignados
                            </h3>
                            <p class="text-[11px] text-gray-500 mt-0.5">Asocie o remueva programas y utilidades del equipo físico <span class="font-bold text-ugel-guinda">{{ form.cod_informatica }}</span></p>
                        </div>
                        <button
                            type="button"
                            class="text-gray-400 hover:text-gray-500 p-1.5 hover:bg-gray-100 rounded-lg transition"
                            @click="showModalProgramas = false"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body / Columns -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-12 bg-gray-50/30 overflow-y-auto flex-1">
                        
                        <!-- Columna Izquierda: Software Disponible -->
                        <div class="md:col-span-5 bg-white border border-gray-200/80 rounded-xl shadow-sm flex flex-col max-h-[55vh]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Software Disponible</span>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-650 border border-gray-200/50">
                                        {{ filteredLeftPrograms.length }} de {{ leftPrograms.length }}
                                    </span>
                                </div>
                                
                                <!-- Buscador -->
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchPrograma"
                                        type="text"
                                        class="block w-full rounded-lg border border-gray-300 bg-white pl-9 pr-8 py-1.5 text-xs placeholder-gray-400 focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        placeholder="Buscar por nombre, código o tipo..."
                                    />
                                    <button 
                                        v-if="searchPrograma" 
                                        @click="searchPrograma = ''" 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Listado Izquierda -->
                            <div class="p-2 overflow-y-auto flex-1 divide-y divide-gray-100/50 min-h-[30vh]">
                                <div 
                                    v-for="prog in filteredLeftPrograms" 
                                    :key="prog.id" 
                                    @click="toggleSelectLeft(prog)"
                                    @dblclick="transferToRight([prog])"
                                    :class="[
                                        'p-2.5 rounded-lg transition-all duration-150 cursor-pointer flex items-center justify-between group my-0.5 border border-transparent',
                                        isSelectedLeft(prog) ? 'bg-blue-50/60 border-blue-200/80 shadow-sm' : 'hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-center gap-3 pr-2 min-w-0">
                                        <input
                                            type="checkbox"
                                            :checked="isSelectedLeft(prog)"
                                            @click.stop="toggleSelectLeft(prog)"
                                            class="size-3.5 rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul cursor-pointer shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate leading-snug">{{ prog.nombre }}</p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[9px] font-semibold text-gray-400 capitalize">{{ prog.tipo || 'Programa' }}</span>
                                                <span class="text-[9px] font-mono text-gray-400">• {{ prog.cod_informatica }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click.stop="transferToRight([prog])"
                                        class="opacity-0 group-hover:opacity-100 transition p-1 hover:bg-blue-100 hover:text-ugel-azul text-gray-400 rounded shrink-0"
                                        title="Instalar en el equipo"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="filteredLeftPrograms.length === 0" class="py-12 text-center text-xs text-gray-450 flex flex-col items-center justify-center">
                                    <svg class="size-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    No hay software disponible para asociar.
                                </div>
                            </div>

                            <!-- Footer Izquierda: Acción rápida -->
                            <div class="p-3 bg-gray-50/50 border-t border-gray-100 rounded-b-xl flex items-center justify-between text-xs">
                                <span class="text-[10px] text-gray-400">Doble clic para transferir rápido</span>
                                <button
                                    type="button"
                                    :disabled="filteredLeftPrograms.length === 0"
                                    @click="addAllToRight"
                                    class="text-ugel-azul hover:text-ugel-guinda font-semibold disabled:opacity-40 disabled:text-gray-400 transition"
                                >
                                    Instalar todos ({{ filteredLeftPrograms.length }})
                                </button>
                            </div>
                        </div>

                        <!-- Columna Centro: Botones de control -->
                        <div class="md:col-span-2 flex md:flex-col items-center justify-center gap-2.5 py-2 md:py-0">
                            <!-- Agregar seleccionados -->
                            <button
                                type="button"
                                :disabled="selectedProgramasLeft.length === 0"
                                @click="addSelectedToRight"
                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-blue-300 hover:bg-blue-50/30 p-2 text-xs font-semibold text-gray-700 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-450 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                                title="Asignar seleccionados"
                            >
                                <svg class="size-4 text-gray-600 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                                <svg class="size-4 text-gray-600 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Quitar seleccionados -->
                            <button
                                type="button"
                                :disabled="selectedProgramasRight.length === 0"
                                @click="removeSelectedFromRight"
                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-red-300 hover:bg-red-50/30 p-2 text-xs font-semibold text-gray-700 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-455 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                                title="Desasignar seleccionados"
                            >
                                <svg class="size-4 text-gray-600 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                <svg class="size-4 text-gray-600 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>

                            <!-- Agregar todos -->
                            <button
                                type="button"
                                :disabled="leftPrograms.length === 0"
                                @click="addAllToRight"
                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-blue-300 hover:bg-blue-50/30 p-2 text-xs font-bold text-gray-750 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-400 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                                title="Asignar todo"
                            >
                                <span class="hidden md:inline-flex items-center font-mono">»</span>
                                <span class="md:hidden font-mono">▼▼</span>
                            </button>

                            <!-- Quitar todos -->
                            <button
                                type="button"
                                :disabled="localProgramas.length === 0"
                                @click="removeAllFromRight"
                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-red-300 hover:bg-red-50/30 p-2 text-xs font-bold text-gray-750 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-400 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                                title="Desasignar todo"
                            >
                                <span class="hidden md:inline-flex items-center font-mono">«</span>
                                <span class="md:hidden font-mono">▲▲</span>
                            </button>
                        </div>

                        <!-- Columna Derecha: Software Asignado -->
                        <div class="md:col-span-5 bg-white border border-gray-200/80 rounded-xl shadow-sm flex flex-col max-h-[55vh]">
                            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl flex items-center justify-between min-h-[73px]">
                                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Software en este Equipo</span>
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200/50">
                                    {{ localProgramas.length }}
                                </span>
                            </div>

                            <!-- Listado Derecha -->
                            <div class="p-2 overflow-y-auto flex-1 divide-y divide-gray-100/50 min-h-[30vh]">
                                <div 
                                    v-for="prog in localProgramas" 
                                    :key="prog.id" 
                                    @click="toggleSelectRight(prog)"
                                    @dblclick="transferToLeft([prog])"
                                    :class="[
                                        'p-2.5 rounded-lg transition-all duration-150 cursor-pointer flex items-center justify-between group my-0.5 border border-transparent',
                                        isSelectedRight(prog) ? 'bg-red-50/60 border-red-200/80 shadow-sm' : 'hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-center gap-3 pr-2 min-w-0">
                                        <input
                                            type="checkbox"
                                            :checked="isSelectedRight(prog)"
                                            @click.stop="toggleSelectRight(prog)"
                                            class="size-3.5 rounded border-gray-300 text-red-500 focus:ring-red-500 cursor-pointer shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate leading-snug">{{ prog.nombre }}</p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[9px] font-semibold text-gray-400 capitalize">{{ prog.tipo || 'Programa' }}</span>
                                                <span class="text-[9px] font-mono text-gray-400">• {{ prog.cod_informatica }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click.stop="transferToLeft([prog])"
                                        class="opacity-0 group-hover:opacity-100 transition p-1 hover:bg-red-100 hover:text-red-600 text-gray-400 rounded shrink-0"
                                        title="Desinstalar del equipo"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="localProgramas.length === 0" class="py-12 text-center text-xs text-gray-450 flex flex-col items-center justify-center">
                                    <svg class="size-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    El equipo no tiene programas asignados.
                                </div>
                            </div>

                            <!-- Footer Derecha: Acción rápida -->
                            <div class="p-3 bg-gray-50/50 border-t border-gray-100 rounded-b-xl flex items-center justify-between text-xs">
                                <span class="text-[10px] text-gray-400">Doble clic para transferir rápido</span>
                                <button
                                    type="button"
                                    :disabled="localProgramas.length === 0"
                                    @click="removeAllFromRight"
                                    class="text-red-650 hover:text-red-800 font-semibold disabled:opacity-40 disabled:text-gray-400 transition"
                                >
                                    Quitar todos ({{ localProgramas.length }})
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4 bg-gray-50 rounded-b-2xl">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                            @click="showModalProgramas = false"
                            :disabled="savingProgramas"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-ugel-guinda disabled:opacity-50 disabled:bg-gray-400 transition"
                            @click="saveProgramas"
                            :disabled="savingProgramas"
                        >
                            <svg v-if="savingProgramas" class="size-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Drawer de Historial de Cambios -->
        <transition
            enter-active-class="transition-opacity ease-out duration-300"
            leave-active-class="transition-opacity ease-in duration-200"
        >
            <div v-if="showHistorialDrawer" class="fixed inset-0 bg-gray-500/75 transition-opacity z-50" @click="showHistorialDrawer = false"></div>
        </transition>

        <transition
            enter-active-class="transform transition ease-in-out duration-300 sm:duration-500"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-300 sm:duration-500"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showHistorialDrawer" class="fixed inset-y-0 right-0 max-w-full pl-10 flex z-50">
                <div class="w-screen max-w-xl bg-white shadow-2xl flex flex-col border-l border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-6 bg-gradient-to-r from-ugel-guinda to-ugel-guinda/90 text-white flex items-center justify-between shadow-md">
                        <div>
                            <h3 class="text-lg font-bold">Historial de cambios</h3>
                            <p class="text-xs text-white/80 mt-1">Registros de acciones realizadas sobre este equipo</p>
                        </div>
                        <button 
                            type="button" 
                            class="rounded-md text-white/80 hover:text-white focus:outline-none transition p-1 hover:bg-white/10"
                            @click="showHistorialDrawer = false"
                        >
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-6 bg-gray-50 relative">
                        <div v-if="loadingHistorial" class="flex flex-col items-center justify-center py-20 gap-3">
                            <svg class="animate-spin h-8 w-8 text-ugel-azul" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-500">Cargando historial...</span>
                        </div>

                        <div v-else-if="historial.length === 0" class="flex flex-col items-center justify-center py-20 text-center gap-4">
                            <div class="rounded-full bg-gray-100 p-4 text-gray-400">
                                <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-700">Sin movimientos registrados</h4>
                            <p class="text-xs text-gray-500 max-w-xs">Aún no se han registrado movimientos o cambios para este registro.</p>
                        </div>

                        <!-- Timeline -->
                        <div v-else class="relative border-l-2 border-gray-200 ml-4 pl-6 space-y-8">
                            <div v-for="item in historial" :key="item.id" class="relative group">
                                <!-- Bullet point indicator -->
                                <span class="absolute -left-[33px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white ring-2"
                                      :class="[
                                          item.tipo_accion === 'CREACION' ? 'ring-emerald-500' : 
                                          item.tipo_accion === 'ELIMINACION' ? 'ring-rose-500' : 'ring-indigo-500'
                                      ]">
                                    <span class="h-1.5 w-1.5 rounded-full"
                                          :class="[
                                              item.tipo_accion === 'CREACION' ? 'bg-emerald-500' : 
                                              item.tipo_accion === 'ELIMINACION' ? 'bg-rose-500' : 'bg-indigo-500'
                                          ]"></span>
                                </span>

                                <!-- Box Container -->
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-all duration-200 group-hover:border-gray-200">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide border"
                                              :class="[
                                                  item.tipo_accion === 'CREACION' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/50' : 
                                                  item.tipo_accion === 'ELIMINACION' ? 'bg-rose-50 text-rose-700 border-rose-200/50' : 
                                                  'bg-indigo-50 text-indigo-700 border-indigo-200/50'
                                              ]">
                                            {{ item.tipo_accion }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-medium">
                                            {{ formatDate(item.fecha_hora) }}
                                        </span>
                                    </div>

                                    <div v-if="getDescripcionLines(item.descripcion).length > 1" class="mb-3">
                                        <ul class="list-disc pl-5 space-y-1.5 text-sm text-gray-600">
                                            <li v-for="(line, idx) in getDescripcionLines(item.descripcion)" :key="idx" class="leading-relaxed">
                                                {{ line }}
                                            </li>
                                        </ul>
                                    </div>
                                    <p v-else class="text-sm text-gray-600 font-medium leading-relaxed mb-3 whitespace-pre-line" style="white-space: pre-line">
                                        {{ item.descripcion }}
                                    </p>

                                    <!-- User metadata footer -->
                                    <div class="flex items-center gap-2 pt-2.5 border-t border-gray-50 text-xs text-gray-500">
                                        <div class="h-6 w-6 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600 uppercase border border-gray-200">
                                            {{ item.usuario?.name?.charAt(0) || 'U' }}
                                        </div>
                                        <div class="truncate">
                                            <span class="font-semibold text-gray-700 block truncate leading-none mb-0.5">{{ item.usuario?.name || 'Usuario' }}</span>
                                            <span class="text-[10px] text-gray-400 block truncate leading-none">{{ item.usuario?.email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Drawer de Diagnóstico de IA -->
        <transition
            enter-active-class="transition-opacity ease-out duration-300"
            leave-active-class="transition-opacity ease-in duration-200"
        >
            <div v-if="showAIDrawer" class="fixed inset-0 bg-gray-500/75 transition-opacity z-50" @click="showAIDrawer = false"></div>
        </transition>

        <transition
            enter-active-class="transform transition ease-in-out duration-300 sm:duration-500"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-300 sm:duration-500"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showAIDrawer" class="fixed inset-y-0 right-0 max-w-full pl-10 flex z-50">
                <div class="w-screen max-w-xl bg-white shadow-2xl flex flex-col border-l border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-6 bg-gradient-to-r from-purple-700 to-indigo-800 text-white flex items-center justify-between shadow-md">
                        <div>
                            <div class="flex items-center gap-2">
                                <svg class="size-6 text-purple-200 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.187.904zM18 7.5l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5zM21 16l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5z" />
                                </svg>
                                <h3 class="text-lg font-bold">Diagnóstico con IA</h3>
                            </div>
                            <p class="text-xs text-purple-100 mt-1">Análisis predictivo y recomendaciones técnicas en base a su historial y características</p>
                        </div>
                        <button 
                            type="button" 
                            class="rounded-md text-white/80 hover:text-white focus:outline-none transition p-1 hover:bg-white/10"
                            @click="showAIDrawer = false"
                        >
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-6 bg-gray-50/50 space-y-6">
                        <!-- Ficha de Contexto Técnico Evaluado Simplificado -->
                        <div class="bg-gradient-to-r from-purple-500/5 to-indigo-500/5 rounded-xl p-4 border border-purple-100/80 shadow-sm flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-purple-100/50 text-purple-700 shrink-0">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="space-y-1 text-xs">
                                <h4 class="font-extrabold text-purple-950 uppercase tracking-wider">Acceso a Datos del Sistema</h4>
                                <p class="text-gray-600 leading-normal">
                                    La IA está integrada y tiene acceso en tiempo real a las especificaciones técnicas del hardware, programas asignados, historial de movimientos y datos de custodia de este equipo (<strong class="font-bold text-gray-800">{{ form.cod_informatica }}</strong>).
                                </p>
                            </div>
                        </div>

                        <!-- Formulario de problema -->
                        <div class="space-y-3">
                            <label for="problema_text" class="block text-sm font-semibold text-gray-700">Describa la falla u observación técnica</label>
                            <textarea
                                id="problema_text"
                                v-model="problemaText"
                                rows="4"
                                class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 bg-white"
                                placeholder="Escriba los síntomas de la falla (ej: la laptop se calienta y se apaga sola a los pocos minutos)..."
                            ></textarea>
                            
                            <!-- Sugerencias / Chips -->
                            <div class="space-y-1.5">
                                <span class="text-[11px] font-medium text-gray-400 block">Sugerencias rápidas:</span>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="sug in suggestions"
                                        :key="sug"
                                        type="button"
                                        @click="selectSugerencia(sug)"
                                        class="px-2.5 py-1 rounded-full bg-purple-50 hover:bg-purple-100 border border-purple-200/60 text-purple-700 text-xs transition duration-150 font-medium"
                                    >
                                        + {{ sug }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de acción -->
                        <div>
                            <button
                                type="button"
                                :disabled="loadingDiag || !problemaText.trim()"
                                @click="generarDiagnostico"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-purple-800 disabled:opacity-40 disabled:bg-gray-400 transition-all duration-200"
                            >
                                <svg v-if="loadingDiag" class="size-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="size-4 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                <span>{{ loadingDiag ? 'Procesando diagnóstico...' : 'Iniciar Diagnóstico con IA' }}</span>
                            </button>
                        </div>

                        <!-- Result Section -->
                        <div v-if="loadingDiag || diagnosticoResultado" class="space-y-4 pt-4 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                                    </span>
                                    Reporte de Diagnóstico IA
                                </h4>
                                
                                <div v-if="diagnosticoResultado && !loadingDiag" class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="copiarDiagnostico"
                                        class="px-2.5 py-1 text-xs border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-700 font-medium transition flex items-center gap-1"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                        <span>{{ copyDiagCopiado ? 'Copiado!' : 'Copiar' }}</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="aplicarAObservacion"
                                        class="px-2.5 py-1 text-xs border border-purple-250 rounded-md bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition flex items-center gap-1"
                                        title="Establecer como Observación técnica de baja"
                                    >
                                        <svg class="size-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                        </svg>
                                        <span>Copiar a Observación de Baja</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Skeleton loader -->
                            <div v-if="loadingDiag" class="bg-white rounded-xl border border-gray-200/80 p-5 space-y-4 shadow-sm animate-pulse">
                                <div class="h-4 bg-gray-200 rounded-full w-2/5"></div>
                                <div class="space-y-2">
                                    <div class="h-3 bg-gray-200 rounded-full"></div>
                                    <div class="h-3 bg-gray-200 rounded-full w-5/6"></div>
                                    <div class="h-3 bg-gray-200 rounded-full w-4/5"></div>
                                </div>
                                <div class="h-4 bg-gray-200 rounded-full w-1/3"></div>
                                <div class="space-y-2">
                                    <div class="h-3 bg-gray-200 rounded-full"></div>
                                    <div class="h-3 bg-gray-200 rounded-full w-11/12"></div>
                                </div>
                            </div>

                            <!-- Diagnostic Result Output Card -->
                            <div v-else class="bg-white rounded-xl border border-purple-100/90 p-5 shadow-sm space-y-1 bg-gradient-to-b from-white to-purple-50/20">
                                <div v-html="formatMarkdown(diagnosticoResultado)"></div>
                                
                                <div v-if="diagnosticoProveedor" class="flex justify-end pt-3 border-t border-purple-100/50 mt-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 border border-purple-200 text-purple-700 shadow-sm">
                                        <svg class="size-3 text-purple-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Procesado con {{ diagnosticoProveedor }} ({{ diagnosticoModelo }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>

<?php

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    complaint-suggestion
    entity-actions
    entity-admins
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-header
    entity-links
    entity-list
    entity-owner
    entity-related-agents
    entity-seals
    entity-social-media
    entity-terms
    link-project
    mapas-breadcrumb
    mapas-container
    share-links
    tabs
');

$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => i::__('Projetos'), 'url' => $app->createUrl('search', 'projects')],
    ['label' => $entity->name, 'url' => $app->createUrl('project', 'single', [$entity->id])],
];
?>

<div class="main-app">
    <mapas-breadcrumb></mapas-breadcrumb>
    <entity-header :entity="entity"></entity-header>
    <tabs class="tabs">
        <tab icon="exclamation" label="<?= i::_e('Informações') ?>" slug="info">
            <div class="tabs__info">
                <mapas-container>
                    <main>
                        <div class="grid-12">
                            <div class="col-12 additional">
                                <div v-if="entity.emailPublico || entity.telefonePublico" class="additional-info">
                                    <span class="additional-info__title"><?php i::_e("Informações adicionais"); ?></span>
                                    <div v-if="entity.telefonePublico" class="additional-info__content">
                                        <span class="info-item"><?php i::_e("telefone:"); ?></span> <label class="info-content">{{entity.telefonePublico}}</label>
                                    </div>
                                    <div v-if="entity.emailPublico" class="additional-info__content">
                                        <span class="info-item"><?php i::_e("email:"); ?></span> <label class="info-content">{{entity.emailPublico}}</label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="entity.longDescription!=null" class="col-12">
                                <h2><?php i::_e('Descrição Detalhada'); ?></h2>
                                <p>{{entity.longDescription}}</p>
                            </div>
                            <entity-files-list v-if="entity.files.downloads!= null" :entity="entity" classes="col-12" group="downloads" title="<?php i::esc_attr_e('Arquivos para download'); ?>"></entity-files-list>
                            <entity-gallery-video :entity="entity" classes="col-12"></entity-gallery-video>
                            <entity-gallery :entity="entity" classes="col-12"></entity-gallery>
                            <div v-if=" entity.relatedOpportunities?.length>0 || entity.children?.length>0" class="col-12">
                                <h4><?php i::_e('Propriedades do Projeto'); ?></h4>
                                    <entity-list v-if="entity.children?.length>0" title="<?php i::esc_attr_e('Subprojetos'); ?>" type="project" :ids="entity.children"></entity-list>
                                    <entity-list title="<php i::esc_attr_e('Oportunidades');?>"  type="opportunity" :ids="[...(entity.ownedOpportunities ? entity.ownedOpportunities : []), ...(entity.relatedOpportunities ? entity.relatedOpportunities : [])]"></entity-list>
                                    
                            </div>
                        </div>
                    </main>
                    <aside>
                        <div class="grid-12">
                            <entity-social-media :entity="entity" classes="col-12"></entity-social-media>
                            <entity-seals :entity="entity" :editable="entity.currentUserPermissions?.createSealRelation" classes="col-12" title="<?php i::esc_attr_e('Verificações'); ?>"></entity-seals>
                            <entity-related-agents :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Agentes Relacionados'); ?>"></entity-related-agents>
                            <entity-terms :entity="entity" classes="col-12" taxonomy="tag" title="<?php i::esc_attr_e('Tags') ?>"></entity-terms>
                            <share-links classes="col-12" title="<?php i::esc_attr_e('Compartilhar'); ?>" text="<?php i::esc_attr_e('Veja este link:'); ?>"></share-links>
                            <entity-owner classes="col-12" title="<?php i::esc_attr_e('Publicado por'); ?>" :entity="entity"></entity-owner>
                            <entity-admins :entity="entity" classes="col-12"></entity-admins>

                        </div>
                    </aside>
                    <aside>
                        <div class="grid-12">
                            <complaint-suggestion :entity="entity"></complaint-suggestion>
                        </div>
                    </aside>
                </mapas-container>
                <entity-actions :entity="entity"></entity-actions>
            </div>
        </tab>
    </tabs>
</div>
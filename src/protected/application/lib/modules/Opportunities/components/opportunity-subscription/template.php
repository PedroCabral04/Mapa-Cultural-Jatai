<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
	mc-loading
	select-entity
');
?>
<div class="grid-12 opportunity-subscription">
	<div class="col-12 opportunity-subscription__info">
		<p class="title">
			<?= i::__("Período de inscrição") ?>
		</p>

		<div class="content">
			<div class="content__description" v-html="infoRegistration"></div>
		</div>
	</div>

	<div v-if="isOpen && !isPublished && !registrationLimit && !registrationLimitPerOwner" class="col-12 opportunity-subscription__subscription">
		<p class="title"> <?= i::__("Inscreva-se") ?> </p>

		<div v-if="global.auth.isLoggedIn" class="logged">
			<p v-if="categories && entitiesLength > 1" class="logged__description"> <?= i::__("Escolha um Agente Cultural e uma categoria para fazer a inscrição.") ?> </p>			
			<p v-if="!categories && entitiesLength > 1" class="logged__description"> <?= i::__("Escolha um Agente Cultural para fazer a inscrição.") ?> </p>
			<p v-if="categories && entitiesLength == 1" class="logged__description"> <?= i::__("Escolha uma categoria para fazer a inscrição.") ?> </p>
			<p v-if="!categories && entitiesLength == 1" class="logged__description"> <?= i::__("Clique no botão abaixo para fazer a inscrição.") ?> </p>

			<!-- Logado -->
			<form class="logged__form grid-12" @submit.prevent>
				<div class="col-6 sm:col-12">
					<select-entity type="agent" openside="down-right" :query="{'type': 'EQ(1)'}" select="name,files.avatar,endereco,location" @fetch="fetch($event)" @select="selectAgent($event)" classes="opportunity-subscription__popover">
						<template #button="{ toggle }">
							<span v-if="!agent" class="fakeInput" @click="toggle()">
								<div class="fakeInput__img">
									<mc-icon name="image"></mc-icon>
								</div>
								<?= i::_e('Agente Cultural') ?>
							</span>

							<span v-if="agent" class="fakeInput" @click="toggle()">
								<mc-icon name="selected"></mc-icon>
								<div class="fakeInput__img">
									<img :src="agent.files?.avatar?.transformations?.avatarSmall?.url" />
								</div>
								{{agent.name}}
							</span>

						</template>
					</select-entity>
				</div>

				<div v-if="categories" class="col-6 sm:col-12 field">
					<select name="category" v-model="category">
						<option value="null" disabled selected> <?= i::__('Categoria') ?> </option>
						<option v-for="category in categories" :value="category"> {{category}} </option>
					</select>
				</div>

				<button v-if="!processing" @click="subscribe()" class="col-12 button button--xbg button--primary button--large">
					<?= i::__("Fazer inscrição") ?>
				</button>
				<div v-if="processing" class="col-12">
					<mc-loading :condition="processing"> <?= i::__('Fazendo inscrição') ?></mc-loading>
				</div>
			</form>
		</div>

		<!-- Deslogado -->
		<div v-if="!global.auth.isLoggedIn" class="loggedOut">
			<p class="loggedOut__description">
				<?= i::__("Você precisa acessar sua conta ou  criar uma cadastro na plataforma para poder se inscrever em editais ou oportunidades") ?>
			</p>

			<button class="col-12 button button--xbg button--primary button--large" @click="redirectLogin">
				<?= i::__("Fazer inscrição") ?>
			</button>
		</div>
	</div>
</div>
@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-2 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-0">Dashboard</p>
				</div>
			</div>
			<div v-if="loaded.stats" class="row">
				<div class="col-xl-3 col-md-4">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Total posts</h5>
									<span class="h2 font-weight-bold mb-0" v-text="stats.statuses"></span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-image"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-md-4">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Local posts</h5>
									<span class="h2 font-weight-bold mb-0" v-text="stats.real_statuses"></span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-image"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-md-4">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Total users</h5>
									<span class="h2 font-weight-bold mb-0" v-text="stats.users"></span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-circle-08"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-md-4">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Reports</h5>
									<span class="h2 font-weight-bold mb-0" v-text="stats.reports"></span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-bell-55"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-md-4">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Messages</h5>
									<span class="h2 font-weight-bold mb-0" v-text="stats.contact"></span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
										<i class="ni ni-chat-round"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container-fluid mt-4">

	<div class="row">
		<div class="col-md-4">
			<div class="card bg-default">
				<div class="card-header bg-transparent">
					<div class="row align-items-center">
						<div class="col">
							<h6 class="text-light text-uppercase ls-1 mb-1">New</h6>
							<h5 class="h3 text-white mb-0">Accounts</h5>
						</div>
					</div>
				</div>
				<div v-if="!loaded.accounts" class="card-body text-center">
					<b-spinner class="mb-4"></b-spinner>
				</div>
				<div v-else class="list-group list-group-scroll">
					<div
						v-for="(item, index) in accounts"
						class="list-group-item">

						<div class="d-flex align-items-center mr-1">
							<div class="custom-control custom-checkbox account-select-check">
								<input type="checkbox" class="custom-control-input" :id="'ac:' + item.id" :disabled="item.status && item.status == 'deleted' || item.hasOwnProperty('is_admin') && item.is_admin" @@change="handleAccountSelected($event, item, index)">
								<label class="custom-control-label" :for="'ac:' + item.id"></label>
							</div>
							<template v-if="item.hasOwnProperty('user_id')">
								<a :href="`/i/admin/users/show/${item.user_id}`" class="d-flex flex-row align-items-center">
									<img :src="item.avatar" class="avatar" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';"/>
									<div v-if="item.status && item.status == 'deleted'">
										<span v-text="item.username" class="font-weight-bold text-danger">Loading...</span>
										<span class="ml-2 badge badge-danger">Deleted</span>
									</div>
									<div v-else>
										<div v-text="item.username" class="font-weight-bold">Loading...</div>
										<div v-if="item.note_text" v-text="renderNote(item.note_text)" class="note">Loading...</div>
									</div>
								</a>
							</template>
							<template v-else>
								<span class="d-flex flex-row align-items-center">
									<img :src="item.avatar" class="avatar" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';"/>
									<div v-if="item.status && item.status == 'deleted'">
										<span v-text="item.username" class="font-weight-bold text-danger">Loading...</span>
										<span class="ml-2 badge badge-danger">Deleted</span>
									</div>
									<div v-else>
										<div v-text="item.username" class="font-weight-bold">Loading...</div>
										<div v-if="item.note_text" v-text="renderNote(item.note_text)" class="note">Loading...</div>
									</div>
								</span>
							</template>
						</div>

						<div>
							<div class="d-flex" style="font-size: 13px;">
								<div v-text="timeAgo(item.created_at)" class="small text-light"></div>
							</div>
						</div>
					</div>

					<a v-if="pagination.accounts" class="list-group-item font-weight-bold justify-content-center" href="#" @click.prevent="loadMoreAccounts()">Load more</a>
				</div>

			</div>
			<template v-if="loaded.accounts && accountsSelected && accountsSelected.length">
				<a
					class="btn btn-danger font-weight-bold btn-block mt-n4 mb-3"
					href="#"
					@@click.prevent="handleSelectedDeletes">
					Delete Selected Accounts
				</a>
			</template>
		</div>

		<div class="col-md-4">
			<div class="card bg-default">
				<div class="card-header bg-transparent">
					<div class="row align-items-center">
						<div class="col">
							<h6 class="text-light text-uppercase ls-1 mb-1">New</h6>
							<h5 class="h3 text-white mb-0">Posts</h5>
						</div>
					</div>
				</div>
				<div v-if="!loaded.posts" class="card-body text-center">
					<b-spinner class="mb-4"></b-spinner>
				</div>
				<div v-else class="list-group list-group-scroll">
					<a
						v-for="(item, index) in posts"
						class="list-group-item"
						:href="`/i/web/post/${item.id}`">

						<div v-if="item.account" class="d-flex align-items-center mr-1">
							<img :src="item.account.avatar" class="avatar" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';"/>
							<div>
								<div v-text="item.account.acct" class="font-weight-bold">Loading...</div>
								<div v-if="item.content" v-text="renderNote(item.content_text)" class="note">Loading...</div>
								<div v-else class="badge badge-primary" v-text="item.pf_type" style="font-size:9px"></div>
							</div>
						</div>
						<div v-else>
							<div class="text-muted font-weight-bold">Deleted or unavailable post</div>
						</div>

						<div>
							<div v-if="item.account" class="d-flex" style="font-size: 13px;">
								<div v-text="timeAgo(item.created_at)" class="small text-light"></div>
							</div>
						</div>
					</a>

					<a v-if="pagination.posts" class="list-group-item font-weight-bold justify-content-center" href="#" @click.prevent="loadMorePosts()">Load more</a>
				</div>
			</div>
		</div>

		<div class="col-md-4">
			<div class="card bg-default">
				<div class="card-header bg-transparent">
					<div class="row align-items-center">
						<div class="col">
							<h6 class="text-light text-uppercase ls-1 mb-1">New</h6>
							<h5 class="h3 text-white mb-0">Instances</h5>
						</div>
					</div>
				</div>
				<div v-if="!loaded.instances" class="card-body text-center">
					<b-spinner class="mb-4"></b-spinner>
				</div>
				<div v-else class="list-group list-group-scroll">
					<a
						v-for="(item, index) in instances"
						class="list-group-item"
						:href="`/i/admin/instances/show/${item.id}`">

						<div v-text="item.domain" class="font-weight-bold">Loading...</div>

						<div>
							<div class="d-flex" style="font-size: 13px;">
								<div v-if="item.software" class="badge badge-secondary mr-2" v-text="item.software"></div>
								<div v-if="item.user_count" class="badge badge-primary mr-2">
									<span class="mr-1"><i class="far fa-user"></i></span>
									<span v-text="item.user_count"></span>
								</div>
								<div v-text="timeAgo(item.created_at)" class="small text-light"></div>
							</div>
						</div>
					</a>

					<a v-if="pagination.instances" class="list-group-item font-weight-bold justify-content-center" href="#" @click.prevent="loadMoreInstances()">Load more</a>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
<script type="text/javascript">
	let app = new Vue({
		el: '#panel',

		data: {
			stats: {
				"contact": 0,
				"contact_monthly": 0,
				"reports": 0,
				"reports_monthly": 0,
				"failedjobs": 0,
				"statuses": 0,
				"statuses_monthly": 0,
				"profiles": 0,
				"users": 0,
				"users_monthly": 0,
				"instances": 0,
				"media": 0,
				"storage": 0,
				"posts_this_week": [],
				"posts_last_week": []
			},
			loaded: {
				stats: false,
				accounts: false,
				posts: false,
				instances: false
			},
			pagination: {
				accounts: false,
				posts: false,
				instances: false
			},
			accounts: [],
			posts: [],
			instances: [],
			accountsSelected: []
		},

		mounted() {
			this.fetchStats();
		},

		methods: {
			fetchStats() {
				axios.get('/i/admin/api/stats')
				.then(res => {
					this.stats = res.data;
					this.loaded.stats = true;
					this.fetchAccounts();
				})
			},

			fetchAccounts() {
				axios.get('/i/admin/api/accounts')
				.then(res => {
					this.accounts = res.data.data;
					this.loaded.accounts = true;
					this.pagination.accounts = res.data.next_page_url;

					this.fetchPosts();
				})
			},

			loadMoreAccounts() {
				axios.get(this.pagination.accounts)
				.then(res => {
					this.accounts.push(...res.data.data);
					this.pagination.accounts = res.data.next_page_url;
				})
			},

			fetchPosts() {
				axios.get('/i/admin/api/posts')
				.then(res => {
					this.posts = res.data.data;
					this.loaded.posts = true;
					this.pagination.posts = res.data.next_page_url;

					this.fetchInstances();
				})
			},

			loadMorePosts() {
				axios.get(this.pagination.posts)
				.then(res => {
					this.posts.push(...res.data.data);
					this.pagination.posts = res.data.next_page_url;
				})
			},

			fetchInstances() {
				axios.get('/i/admin/api/instances')
				.then(res => {
					this.instances = res.data.data;
					this.loaded.instances = true;
					this.pagination.instances = res.data.next_page_url;
				})
			},

			loadMoreInstances() {
				axios.get(this.pagination.instances)
				.then(res => {
					this.instances.push(...res.data.data);
					this.pagination.instances = res.data.next_page_url;
				})
			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			renderNote(val) {
				if(!val) {
					return '';
				}
				if(val.length > 60) {
					return val.slice(0, 60) + ' ...';
				}
				return val;
			},

			handleAccountSelected(event, item, idx) {
				if(event.target.checked) {
					this.accountsSelected.push(...[item]);
				} else {
					this.accountsSelected = this.accountsSelected.filter(a => {
						return a.id !== item.id;
					})
				}
			},

			async handleSelectedDeletes() {
				let wrapper = document.createElement('div');
				let title = document.createElement('div');
				let list = document.createElement('ul');
				list.classList.add('list-group')
				title.innerHTML = '<p class="font-weight-bold text-danger">Are you sure you want to delete the following accounts:</p>';
				wrapper.appendChild(title);
				this.accountsSelected.map(a => {
					let el = document.createElement('li');
					el.classList.add('list-group-item')
					el.classList.add('text-left')
					el.innerHTML = `<div class="media align-items-center">
						<img src="${a.avatar}" width="40" height="40" class="rounded-circle mr-3" onerror="this.src='/storage/avatars/default.png';this.onerror=null;" />
						<div class="media-body">
							<p class="mb-0 username font-weight-bold">${a.username}</p>
							<div class="note small text-muted">${this.renderNote(a.note_text)}</div>
						</div>
					</div>`
					list.appendChild(el)
				})
				wrapper.appendChild(list);
				swal({
					title: 'Confirm',
					content: wrapper,
					icon: 'warning',
					buttons: {
						cancel: "Cancel",
						delete: {
							text: "Delete",
							value: "delete",
							className: "swal-button--danger"
						}
					}
				})
				.then(async (val) => {
					if (val === 'delete') {
						swal({
							title: 'Deleting accounts...',
							icon: 'success',
							timer: 3000,
						});
						await axios.all(this.accountsSelected.map((acct) => this.deleteAccountById(acct)));
						this.fetchAccounts();
						setTimeout(() => {
							let checkboxes = document.querySelectorAll('input[type=checkbox]')
							checkboxes.forEach(checkbox => checkbox.checked = false)
							this.accountsSelected = [];
						}, 500);
					}
				})
				.finally(() => {
				})
			},

			async deleteAccountById(account) {
				await axios.post('/i/admin/users/delete/' + account.user_id)
			}
		}
	});
</script>
@endpush

@push('styles')
<style type="text/css">
	.list-group-scroll {
		max-height: 300px;
		overflow-y: auto;
	}

	.list-group-scroll .list-group-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.list-group-scroll .avatar {
		width: 30px;
		height: 30px;
		border-radius: 30px;
		margin-right: 1rem;
	}

	.list-group-scroll .note {
		color: #bbb;
		font-size: 10px;
		line-height: 12px;
	}
</style>
@endpush

<template>
	<div class="col-12 col-md-9" style="overflow:hidden">
		<div class="row h-100 bg-light justify-content-center">
			<div class="col-12 col-md-10 col-lg-7">
				<div v-if="!initalLoad">
					<p class="text-center mt-5 pt-5 font-weight-bold">Loading...</p>
				</div>
				<div v-else class="px-5">
					<div v-if="emptyFeed">
						<div class="jumbotron mt-5">
							<h1 class="display-4">Hello 👋</h1>
							<h3 class="font-weight-light">Welcome to Pixelfed Groups!</h3>
							<p class="lead">Groups are a way to participate in like minded communities and topics.</p>
							<hr class="my-4">
							<p>Anyone can create and manage their own group as long as it abides by our <a href="#">community guidelines</a>.</p>
							<p class="text-center mb-0">
                                <router-link to="/groups/discover" class="btn btn-primary btn-lg rounded-pill">
                                    Discover Groups
                                </router-link>
							</p>
						</div>
					</div>

					<div v-else>
						<div class="my-4">
		                    <p class="h1 font-weight-bold mb-1">Groups Feed</p>
		                    <p class="lead text-muted mb-0">Recent posts from your groups</p>
		                </div>

						<div class="my-3">
							<group-status
								v-for="(status, index) in feed"
								:key="'gs:' + status.id + index"
								:prestatus="status"
								:profile="profile"
								:show-group-header="true"
								:group="status.group"
								:group-id="status.group.id" />

							<div v-if="feed.length > 2">
								<infinite-loading @infinite="infiniteFeed" :distance="800">
									<div slot="no-more" class="my-3">
										<p class="lead font-weight-bold pt-5">You have reached the end of this feed</p>
										<div style="height: 10rem;"></div>
									</div>
									<div slot="no-results"></div>
								</infinite-loading>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- <div class="col-12 col-md-4">
				<div class="mt-5 media align-items-center">
					<div class="mr-3">
						<i class="fas fa-info-circle fa-2x text-lighter"></i>
					</div>
					<p class="media-body text-muted mb-0 font-weight-light">Groups are in beta, some <a href="#" class="font-weight-bold">limitations</a> apply.</p>
				</div>
			</div> -->
		</div>
	</div>
</template>

<script type="text/javascript">
	import GroupStatus from './GroupStatus.vue';

	export default {
		props: {
			profile: {
				type: Object
			}
		},

		data() {
			return {
				feed: [],
				ids: [],
				page: 1,
				tab: 'feed',
				initalLoad: false,
				emptyFeed: true
			};
		},

		components: {
			'group-status': GroupStatus
		},

		mounted() {
			this.fetchFeed();
		},

		methods: {
			fetchFeed() {
				axios.get('/api/v0/groups/self/feed', {
					params: {
						initial: true
					}
				})
				.then(res => {
					this.page++;
					this.feed = res.data;
					this.emptyFeed = this.feed.length === 0;
					this.initalLoad = true;
				})
			},

			infiniteFeed($state) {
				if(this.feed.length < 2 || this.page > 5) {
					$state.complete();
					return;
				}

				axios.get('/api/v0/groups/self/feed', {
					params: {
						page: this.page
					},
				}).then(res => {
					if (res.data.length) {
						let data = res.data;
						let self = this;
						data.forEach(d => {
							if(self.ids.indexOf(d.id) == -1) {
								self.ids.push(d.id);
								self.feed.push(d);
							}
						});
						$state.loaded();
						this.page++;
					} else {
						$state.complete();
					}
				});
			},

			switchTab(tab) {
				this.tab = tab;
			},

			gotoDiscover() {
				this.$emit('switchtab', 'discover');
			}
		}
	}
</script>

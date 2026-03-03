(function($){'use strict';
function runButton(selector,action,nonceKey,loading){$(document).on('click',selector,function(e){e.preventDefault();const $btn=$(this);const $wrap=$btn.closest('.king-addons-ai-seo-tag-status');const postId=$btn.data('post-id');const $spinner=$wrap.find('.spinner');const $result=$wrap.find('.king-addons-ai-seo-tags-result');if(!postId)return;$btn.prop('disabled',true);$spinner.addClass('is-active').css('visibility','visible');$result.text(loading||'...');$.post(kingAddonsAiSeoPostList.ajaxUrl,{action:action,nonce:kingAddonsAiSeoPostList[nonceKey],post_id:postId},function(r){if(r&&r.success){if(r.data&&r.data.tags){$result.text(r.data.tags);}else if(r.data&&r.data.message){$result.text(r.data.message);}else{$result.text('OK');}}else{$result.text((r&&r.data&&r.data.message)||kingAddonsAiSeoPostList.errorText);}}).always(function(){$btn.prop('disabled',false);$spinner.removeClass('is-active').css('visibility','hidden');});});}
$(function(){
runButton('.king-addons-ai-seo-generate-tags','king_addons_ai_seo_generate_single_tags','generateNonce',kingAddonsAiSeoPostList.generatingText);
runButton('.king-addons-ai-seo-append-tags','king_addons_ai_seo_append_single_tags','appendNonce',kingAddonsAiSeoPostList.appendingText);
runButton('.king-addons-ai-seo-regenerate-tags','king_addons_ai_seo_regenerate_single_tags','regenerateNonce',kingAddonsAiSeoPostList.regeneratingText);
runButton('.king-addons-ai-seo-clear-tags','king_addons_ai_seo_clear_single_tags','clearNonce',kingAddonsAiSeoPostList.clearingText);
});
})(jQuery);
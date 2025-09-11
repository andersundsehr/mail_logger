import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';

console.log({Modal});

/**
 * showMailLogModal
 *
 * @param {string} html
 * @param {string} title
 */
function showMailLogModal(html, title) {
  const typo3Window = (opener != null && typeof opener.top.TYPO3 !== 'undefined' ? opener.top : top);
  const buttonClass = Severity.getCssClass(Severity.info);

  const buttons = [{
    text: typo3Window.TYPO3.lang['button.close'] || 'Close',
    btnClass: 'btn-' + buttonClass,
    name: 'ok',
    trigger(event, modal) {
      if (modal) {
        // TYPO3 12:
        modal.hideModal();
      } else {
        // TYPO3 11:
        Modal.dismiss();
      }
    },
  }];
  /** @var {HTMLElement} modal*/
  let modal = Modal.advanced({
    title,
    content: html,
    severity: Severity.info,
    buttons: buttons,
  });
  if (typeof modal.querySelector !== 'function') {
    // TYPO3 11: modal is jQueryObject
    modal = modal[0];
  }

  const afterModalInitialized = () => {
    modal.querySelector('.t3js-modal-content').style.width = '50%';

    const iframe = modal.querySelector('iframe.iframe-content');
    const iframeDocument = iframe.contentDocument || iframe.contentWindow?.document;
    const modalContent = modal.querySelector(iframe.dataset.content).innerHTML;
    if (iframeDocument && modalContent) {
      iframeDocument.write(modalContent);
    } else {
      setTimeout(afterModalInitialized, 10);
    }
  };
  setTimeout(afterModalInitialized);
}


/**
 * loadMailLogModal
 *
 * @param {string} url
 */
async function loadMailLogModal(url) {
  const response = await fetch(url);
  let data = await response.text();
  // Replace jQuery with native DOM parsing
  const wrapper = document.createElement('div');
  wrapper.innerHTML = data;
  const h1 = wrapper.querySelector('h1');
  const title = h1 ? h1.innerHTML : '';
  if (h1) {
    h1.parentNode.removeChild(h1);
  }
  showMailLogModal(wrapper, title);
}

console.log('MailLog: Main.js loaded');
document.querySelectorAll('a.maillogger-open-modal').forEach(
  el => {
    console.log('MailLog: Adding click event listener to', el);
    el.addEventListener('click', (event) => {
      event.preventDefault();
      loadMailLogModal(el.href);
    });
  },
);

import './Modal.css';
import { useModal } from '../context/ModalContext';

function Modal() {
    const { modal, closemodal } = useModal();

    if (!modal.visible) return null;

    return (
        <div className={`modal-back ${modal.closing ? "fade-out" : "fade-in"}`}>
            <div className={`modal-window 
                ${modal.error ? "error" : ""}
                ${modal.closing ? "slide-down" : "slide-up"}
            `}>
                <p>{modal.message}</p>
                <button onClick={closemodal}>OK</button>
            </div>
        </div>
    );
}

export default Modal;

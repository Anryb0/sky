import './Modal.css';
import { useRoot } from '../context/RootContext.jsx';

function Modal() {
    const { modal, closemodal } = useRoot();

    if (!modal.visible) return null;

    return (
        <div className={`modal-back ${modal.closing ? "modal-out" : "fade-in"}`}>
            <div className={`modal-window 
                ${modal.error ? "error" : ""}
                ${modal.closing ? "slide-down" : "slide-up"}
            `}>
                <div>{modal.message}</div>
                <button onClick={closemodal}>OK</button>
            </div>
        </div>
    );
}

export default Modal;
